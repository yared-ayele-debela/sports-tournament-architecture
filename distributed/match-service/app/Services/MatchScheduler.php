<?php

namespace App\Services;

use App\Models\MatchGame;
use App\Services\Clients\TeamServiceClient;
use App\Services\Clients\TournamentServiceClient;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MatchScheduler
{
    protected TeamServiceClient $teamService;
    protected TournamentServiceClient $tournamentService;

    public function __construct(
        TeamServiceClient $teamService,
        TournamentServiceClient $tournamentService
    ) {
        $this->teamService = $teamService;
        $this->tournamentService = $tournamentService;
    }

    public function generateRoundRobin(int $tournamentId): array
    {
        // 1) Fetch all teams from Team Service
        $teams = $this->getTournamentTeams($tournamentId);
        if ($teams->isEmpty()) {
            throw new \Exception("No teams found for tournament {$tournamentId}");
        }

        // 2) Fetch tournament settings from Tournament Service
        $tournament = $this->tournamentService->getTournament($tournamentId);
        if (!$tournament) {
            throw new \Exception("Tournament {$tournamentId} not found");
        }

        $settings = [
            'start_date' => $tournament['start_date'] ?? now()->addDay()->format('Y-m-d'),
            'daily_start_time' => $tournament['daily_start_time'] ?? '09:00',
            'daily_end_time' => $tournament['daily_end_time'] ?? '18:00',
            'match_duration' => $tournament['match_duration'] ?? 90, // minutes
            'win_rest_time' => $tournament['win_rest_time'] ?? 15, // minutes
            'venues' => $tournament['venues'] ?? []
        ];

        if (empty($settings['venues'])) {
            throw new \Exception("No venues configured for tournament {$tournamentId}");
        }

        // 3) Round-robin algorithm
        $rounds = $this->generateRoundRobinMatches($teams);

        // 4) Scheduling logic
        $matches = $this->scheduleMatches($rounds, $settings, $tournamentId);

        return [
            'tournament_id' => $tournamentId,
            'total_teams' => $teams->count(),
            'total_rounds' => count($rounds),
            'total_matches' => count($matches),
            'matches' => $matches
        ];
    }

    protected function getTournamentTeams(int $tournamentId): Collection
    {
        // Fetch teams for this specific tournament
        $response = $this->teamService->getTournamentTeams($tournamentId);
        
        if (!$response || !isset($response['data'])) {
            return collect([]);
        }

        return collect($response['data']);
    }

    protected function generateRoundRobinMatches(Collection $teams): array
    {
        $teamCount = $teams->count();
        
        // If odd number of teams, add a bye (null)
        if ($teamCount % 2 !== 0) {
            $teams->push(null);
            $teamCount++;
        }

        $teamList = $teams->values()->toArray();
        $rounds = [];
        $numRounds = $teamCount - 1;

        // Generate (n-1) rounds
        for ($round = 0; $round < $numRounds; $round++) {
            $roundMatches = [];
            
            // Pair teams for this round
            for ($i = 0; $i < $teamCount / 2; $i++) {
                $homeTeam = $teamList[$i];
                $awayTeam = $teamList[$teamCount - 1 - $i];
                
                // Skip if either team is null (bye)
                if ($homeTeam !== null && $awayTeam !== null) {
                    $roundMatches[] = [
                        'home_team' => $homeTeam,
                        'away_team' => $awayTeam
                    ];
                }
            }
            
            $rounds[] = $roundMatches;
            
            // Rotate teams (except first team which is fixed)
            $this->rotateTeams($teamList);
        }

        return $rounds;
    }

    protected function rotateTeams(array &$teams): void
    {
        // Fix first team, rotate others clockwise
        $firstTeam = $teams[0];
        $lastTeam = array_pop($teams);
        
        // Shift all teams except first one to the right
        for ($i = count($teams) - 1; $i > 0; $i--) {
            $teams[$i] = $teams[$i - 1];
        }
        
        $teams[1] = $lastTeam;
        $teams[0] = $firstTeam;
    }

    protected function scheduleMatches(array $rounds, array $settings, int $tournamentId): array
    {
        $scheduledMatches = [];
        $currentDate = Carbon::parse($settings['start_date'] . ' ' . $settings['daily_start_time']);
        $venueIndex = 0;
        $venues = $settings['venues'];

        foreach ($rounds as $roundNumber => $roundMatches) {
            foreach ($roundMatches as $match) {
                // Check if current time exceeds daily end time
                $dailyEndTime = Carbon::parse($currentDate->format('Y-m-d') . ' ' . $settings['daily_end_time']);
                
                if ($currentDate->gt($dailyEndTime)) {
                    // Move to next day
                    $currentDate = Carbon::parse($currentDate->addDay()->format('Y-m-d') . ' ' . $settings['daily_start_time']);
                }

                // Assign venue (round-robin through available venues)
                $venue = $venues[$venueIndex % count($venues)];
                $venueIndex++;

                // Create match record
                $matchData = [
                    'tournament_id' => $tournamentId,
                    'venue_id' => $venue['id'],
                    'home_team_id' => $match['home_team']['id'],
                    'away_team_id' => $match['away_team']['id'],
                    'referee_id' => $this->assignReferee(), // Would need referee service
                    'match_date' => $currentDate->format('Y-m-d H:i:s'),
                    'round_number' => $roundNumber + 1,
                    'status' => 'scheduled',
                    'home_score' => null,
                    'away_score' => null,
                    'current_minute' => null,
                ];

                $scheduledMatch = MatchGame::create($matchData);
                $scheduledMatches[] = $scheduledMatch;

                // Add match duration + rest time for next match
                $nextMatchTime = $currentDate->addMinutes($settings['match_duration'] + $settings['win_rest_time']);
                $currentDate = $nextMatchTime;
            }
        }

        return $scheduledMatches;
    }

    protected function assignReferee(): int
    {
        // This would integrate with a referee service
        // For now, return a default referee ID
        return 1;
    }
}
