<?php

namespace App\Services;

use App\Models\MatchResult;
use App\Models\Standing;
use App\Services\Clients\MatchServiceClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class StandingsCalculator
{
    protected MatchServiceClient $matchService;

    public function __construct(MatchServiceClient $matchService)
    {
        $this->matchService = $matchService;
    }

    public function updateStandingsFromMatch(MatchResult $result): void
    {
        DB::transaction(function () use ($result) {
            // Get or create standings for both teams
            $homeStanding = Standing::firstOrCreate([
                'tournament_id' => $result->tournament_id,
                'team_id' => $result->home_team_id,
            ]);

            $awayStanding = Standing::firstOrCreate([
                'tournament_id' => $result->tournament_id,
                'team_id' => $result->away_team_id,
            ]);

            // Update home team standings
            $homeStanding->played += 1;
            $homeStanding->goals_for += $result->home_score;
            $homeStanding->goals_against += $result->away_score;

            // Update away team standings
            $awayStanding->played += 1;
            $awayStanding->goals_for += $result->away_score;
            $awayStanding->goals_against += $result->home_score;

            // Determine win/draw/loss and points
            if ($result->home_score > $result->away_score) {
                // Home team wins
                $homeStanding->won += 1;
                $homeStanding->points += 3;
                $awayStanding->lost += 1;
            } elseif ($result->home_score < $result->away_score) {
                // Away team wins
                $awayStanding->won += 1;
                $awayStanding->points += 3;
                $homeStanding->lost += 1;
            } else {
                // Draw
                $homeStanding->drawn += 1;
                $awayStanding->drawn += 1;
                $homeStanding->points += 1;
                $awayStanding->points += 1;
            }

            $homeStanding->save();
            $awayStanding->save();

            // Calculate and update goal_difference and position for both teams
            $this->updateGoalDifferenceAndPosition($result->tournament_id);
        });

        // Clear cached tournament standings
        $this->clearTournamentCache($result->tournament_id);

        // Publish Redis event
        $this->publishStandingsUpdated($result->tournament_id);
    }

    public function recalculateForTournament(int $tournamentId): void
    {
        // Reset standings for tournament
        Standing::where('tournament_id', $tournamentId)->delete();

        // Fetch all completed matches from Match Service
        $matches = $this->matchService->getCompletedMatches($tournamentId);

        Log::info("Recalculating standings for tournament {$tournamentId} with " . count($matches) . " matches.");
        foreach ($matches as $match) {
            // Debug the match structure
            Log::info("Match data: " . json_encode($match));

            // Handle different possible key names for match ID
            $matchId = $match['id'] ?? $match['match_id'] ?? null;

            if (!$matchId) {
                Log::error("Match ID not found in match data", ['match' => $match]);
                continue;
            }

            $matchResult = new MatchResult([
                'match_id' => $matchId,
                'tournament_id' => $tournamentId,
                'home_team_id' => $match['home_team_id'],
                'away_team_id' => $match['away_team_id'],
                'home_score' => $match['home_score'],
                'away_score' => $match['away_score'],
                'completed_at' => $match['completed_at']?? now(),
            ]);

            $this->updateStandingsFromMatch($matchResult);
        }
    }

    public function getTournamentStandings(int $tournamentId): array
    {
        Log::info("Fetching standings for tournament {$tournamentId}");
        $standings = Standing::where('tournament_id', $tournamentId)
            ->orderBy('points', 'desc')
            ->orderBy('goal_difference', 'desc')
            ->orderBy('goals_for', 'desc')
            ->get()
            ->map(function ($standing, $index) {
                $standing->goal_difference = $standing->goals_for - $standing->goals_against;
                $standing->team = $standing->getTeam();
                $standing->position = $index + 1; // Calculate position

                // Update goal_difference in database
                Standing::where('id', $standing->id)
                    ->update(['goal_difference' => $standing->goal_difference, 'position' => $standing->position]);

                return $standing;
            })
            ->toArray();

        return $standings;
    }

    protected function updateGoalDifferenceAndPosition(int $tournamentId): void
    {
        $standings = Standing::where('tournament_id', $tournamentId)
            ->orderBy('points', 'desc')
            ->orderBy('goal_difference', 'desc')
            ->orderBy('goals_for', 'desc')
            ->get();

        foreach ($standings as $index => $standing) {
            $standing->goal_difference = $standing->goals_for - $standing->goals_against;
            $standing->position = $index + 1;
            $standing->save();
        }
    }

    protected function clearTournamentCache(int $tournamentId): void
    {
        Redis::del("tournament_standings:{$tournamentId}");
    }

    protected function publishStandingsUpdated(int $tournamentId): void
    {
        Redis::publish('standings.updated', json_encode([
            'tournament_id' => $tournamentId,
            'timestamp' => now()->toISOString(),
        ]));
    }
}
