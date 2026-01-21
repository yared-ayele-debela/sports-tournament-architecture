<?php

namespace Database\Seeders;

use App\Models\MatchResult;
use App\Services\Clients\MatchServiceClient;
use App\Services\StandingsCalculator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class MatchResultSeeder extends Seeder
{
    protected MatchServiceClient $matchService;
    protected StandingsCalculator $standingsCalculator;

    public function __construct(
        MatchServiceClient $matchService,
        StandingsCalculator $standingsCalculator
    ) {
        $this->matchService = $matchService;
        $this->standingsCalculator = $standingsCalculator;
    }

    public function run(): void
    {
        try {
            // Clear existing data
            MatchResult::query()->delete();

            // Call Match Service to get completed matches
            $tournaments = [1, 2, 3]; // Example tournament IDs
            
            foreach ($tournaments as $tournamentId) {
                $this->processTournamentMatches($tournamentId);
            }

            $this->command->info('Match results and standings seeded successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to seed match results', [
                'error' => $e->getMessage(),
            ]);
            
            $this->command->error('Failed to seed match results: ' . $e->getMessage());
        }
    }

    protected function processTournamentMatches(int $tournamentId): void
    {
        $this->command->info("Processing tournament {$tournamentId}...");
        
        // Get completed matches from Match Service
        $matches = $this->matchService->getCompletedMatchesWithoutAuth($tournamentId);

        // If no matches from service, create mock data for demonstration
        if (!$matches) {
            $this->command->info("No matches found, creating mock data for tournament {$tournamentId}...");
            $matches = $this->createMockMatches($tournamentId);
        }

        foreach ($matches as $match) {
            try {
                // Create match result record
                $matchResult = MatchResult::create([
                    'match_id' => $match['id'],
                    'tournament_id' => $tournamentId,
                    'home_team_id' => $match['home_team_id'],
                    'away_team_id' => $match['away_team_id'],
                    'home_score' => $match['home_score'],
                    'away_score' => $match['away_score'],
                    'completed_at' => $match['completed_at'] ?? now(),
                    
                ]);

                // Update standings using calculator
                $this->standingsCalculator->updateStandingsFromMatch($matchResult);

                $this->command->info("Processed match {$match['id']}: {$match['home_team_id']} {$match['home_score']} - {$match['away_score']} {$match['away_team_id']}");

            } catch (\Exception $e) {
                Log::error('Failed to process match', [
                    'match_id' => $match['id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->command->info("Tournament {$tournamentId} processed successfully!");
    }

    protected function createMockMatches(int $tournamentId): array
    {
        $mockMatches = [];
        
        // Create sample matches for demonstration
        $teams = [
            ['id' => 1, 'name' => 'Team A'],
            ['id' => 2, 'name' => 'Team B'],
            ['id' => 3, 'name' => 'Team C'],
            ['id' => 4, 'name' => 'Team D'],
        ];

        $matchId = 1;
        
        // Round 1 matches
        $mockMatches[] = [
            'id' => $matchId++,
            'home_team_id' => 1,
            'away_team_id' => 2,
            'home_score' => 2,
            'away_score' => 1,
            'completed_at' => now()->subDays(10),
        ];
        
        $mockMatches[] = [
            'id' => $matchId++,
            'home_team_id' => 3,
            'away_team_id' => 4,
            'home_score' => 1,
            'away_score' => 1,
            'completed_at' => now()->subDays(10),
        ];
        
        // Round 2 matches
        $mockMatches[] = [
            'id' => $matchId++,
            'home_team_id' => 2,
            'away_team_id' => 3,
            'home_score' => 3,
            'away_score' => 2,
            'completed_at' => now()->subDays(7),
        ];
        
        $mockMatches[] = [
            'id' => $matchId++,
            'home_team_id' => 4,
            'away_team_id' => 1,
            'home_score' => 0,
            'away_score' => 2,
            'completed_at' => now()->subDays(7),
        ];
        
        // Round 3 matches
        $mockMatches[] = [
            'id' => $matchId++,
            'home_team_id' => 1,
            'away_team_id' => 4,
            'home_score' => 2,
            'away_score' => 2,
            'completed_at' => now()->subDays(3),
        ];
        
        $mockMatches[] = [
            'id' => $matchId++,
            'home_team_id' => 2,
            'away_team_id' => 3,
            'home_score' => 1,
            'away_score' => 0,
            'completed_at' => now()->subDays(3),
        ];
        
        return $mockMatches;
    }
}
