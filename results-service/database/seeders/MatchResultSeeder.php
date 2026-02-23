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

            // Fetch tournaments from Tournament Service
            $this->command->info('Fetching tournaments from Tournament Service...');

            try {
                $tournamentServiceUrl = config('services.tournament_service.url', env('TOURNAMENT_SERVICE_URL', 'http://tournament-service:8002'));
                $response = \Illuminate\Support\Facades\Http::get($tournamentServiceUrl . '/api/tournaments');

                if ($response->status() !== 200) {
                    $this->command->error('Failed to fetch tournaments from Tournament Service. Status: ' . $response->status());
                    $this->command->warn('Skipping match results seeding - no tournaments available');
                    return;
                }

                $responseData = $response->json();
                $tournaments = $responseData['data'] ?? [];

                if (empty($tournaments)) {
                    $this->command->warn('No tournaments found in Tournament Service');
                    $this->command->warn('Skipping match results seeding - no tournaments available');
                    return;
                }

                $this->command->info('Found ' . count($tournaments) . ' tournament(s) to process');

            } catch (\Exception $e) {
                $this->command->error('Error fetching tournaments: ' . $e->getMessage());
                $this->command->warn('Skipping match results seeding - could not fetch tournaments');
                Log::error('Failed to fetch tournaments for seeding', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return;
            }

            // Process each tournament
            foreach ($tournaments as $tournament) {
                $tournamentId = is_array($tournament) ? ($tournament['id'] ?? null) : $tournament;

                if (!$tournamentId) {
                    $this->command->warn('Skipping tournament - invalid ID');
                    continue;
                }

                $this->processTournamentMatches($tournamentId);
            }

            $this->command->info('Match results and standings seeded successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to seed match results', [
                'error' => $e->getMessage(),

                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->command->error('Failed to seed match results: ' . $e->getMessage());
            $this->command->error('File: ' . $e->getFile() . ':' . $e->getLine());
        }
    }

    protected function processTournamentMatches(int $tournamentId): void
    {
        $this->command->info("Processing tournament {$tournamentId}...");

        try {
            // First verify tournament exists in tournament-service
            $tournamentServiceUrl = config('services.tournament_service.url', env('TOURNAMENT_SERVICE_URL', 'http://tournament-service:8002'));
            $tournamentCheck = \Illuminate\Support\Facades\Http::get("{$tournamentServiceUrl}/api/tournaments/{$tournamentId}");

            if ($tournamentCheck->status() !== 200) {
                $this->command->warn("Tournament {$tournamentId} not found in tournament-service, skipping...");
                return;
            }

            // Clear cache first to avoid stale data
            \Illuminate\Support\Facades\Cache::forget("public_tournament:{$tournamentId}:matches:" . md5(serialize(['status' => 'completed'])));

            // Use getCompletedMatchesWithoutAuth which we know works
            $response = $this->matchService->getCompletedMatchesWithoutAuth($tournamentId);

            // Log response for debugging
            Log::info("Match Service response type", [
                'tournament_id' => $tournamentId,
                'response_type' => gettype($response),
                'is_array' => is_array($response),
                'response_keys' => is_array($response) ? array_keys($response) : 'N/A'
            ]);

            // Handle case where response might be boolean true (from cache or error)
            if ($response === true) {
                Log::error("Match Service returned boolean true (likely cache issue)", [
                    'tournament_id' => $tournamentId
                ]);
                $this->command->warn("Cache issue detected for tournament {$tournamentId}, retrying...");
                // Retry without cache
                $response = $this->matchService->getCompletedMatchesWithoutAuth($tournamentId);
            }

            if (!is_array($response)) {
                Log::error("Match Service returned non-array response", [
                    'tournament_id' => $tournamentId,
                    'response_type' => gettype($response),
                    'response' => $response
                ]);
                $this->command->warn("Invalid response type for tournament {$tournamentId}");
                return;
            }

            // Response structure: { "success": true, "data": { "matches": [...], "pagination": {...} } }
            if (!isset($response['success']) || $response['success'] !== true) {
                Log::error("Match Service returned unsuccessful response", [
                    'tournament_id' => $tournamentId,
                    'response' => $response
                ]);
                $this->command->warn("Unsuccessful response for tournament {$tournamentId}");
                return;
            }

            if (!isset($response['data']) || !is_array($response['data'])) {
                Log::error("Match Service response missing or invalid data", [
                    'tournament_id' => $tournamentId,
                    'has_data' => isset($response['data']),
                    'data_type' => isset($response['data']) ? gettype($response['data']) : 'not set'
                ]);
                $this->command->warn("Invalid data structure for tournament {$tournamentId}");
                return;
            }

            // Extract matches from response
            $matches = isset($response['data']['matches']) && is_array($response['data']['matches'])
                ? $response['data']['matches']
                : [];

            if (empty($matches)) {
                Log::info("No matches found for tournament {$tournamentId}");
                $this->command->info("No matches found for tournament {$tournamentId}");
                return;
            }

            $this->command->info("Found " . count($matches) . " completed matches for tournament {$tournamentId}");

            process_matches:
            foreach ($matches as $match) {
                try {
                    // Ensure match is an array
                    if (!is_array($match)) {
                        Log::warning('Match is not an array', [
                            'match' => $match,
                            'match_type' => gettype($match)
                        ]);
                        continue;
                    }

                    // Extract team IDs from nested objects
                    // home_team: { id: 7, name: "Team Eta" }
                    $homeTeamId = null;
                    $awayTeamId = null;

                    if (isset($match['home_team'])) {
                        if (is_array($match['home_team']) && isset($match['home_team']['id'])) {
                            $homeTeamId = $match['home_team']['id'];
                        } elseif (is_numeric($match['home_team'])) {
                            $homeTeamId = $match['home_team'];
                        }
                    }

                    if (isset($match['home_team_id'])) {
                        $homeTeamId = $match['home_team_id'];
                    }

                    if (isset($match['away_team'])) {
                        if (is_array($match['away_team']) && isset($match['away_team']['id'])) {
                            $awayTeamId = $match['away_team']['id'];
                        } elseif (is_numeric($match['away_team'])) {
                            $awayTeamId = $match['away_team'];
                        }
                    }

                    if (isset($match['away_team_id'])) {
                        $awayTeamId = $match['away_team_id'];
                    }

                    if (!$homeTeamId || !$awayTeamId) {
                        Log::warning('Match missing team IDs', [
                            'match_id' => $match['id'],
                            'match' => $match
                        ]);
                        continue;
                    }

                    // Create match result record
                    $matchResult = MatchResult::create([
                        'match_id' => $match['id'],
                        'tournament_id' => $tournamentId,
                        'home_team_id' => $homeTeamId,
                        'away_team_id' => $awayTeamId,
                        'home_score' => $match['home_score'] ?? 0,
                        'away_score' => $match['away_score'] ?? 0,
                        'completed_at' => $match['match_date'] ?? $match['completed_at'] ?? now(),
                    ]);

                    // Update standings using calculator
                    $this->standingsCalculator->updateStandingsFromMatch($matchResult);

                    $homeTeamName = is_array($match['home_team']) ? $match['home_team']['name'] : 'Team ' . $homeTeamId;
                    $awayTeamName = is_array($match['away_team']) ? $match['away_team']['name'] : 'Team ' . $awayTeamId;

                    $this->command->info("Processed match {$match['id']}: {$homeTeamName} {$match['home_score']} - {$match['away_score']} {$awayTeamName}");

                } catch (\Exception $e) {
                    Log::error('Failed to process match', [
                        'match_id' => $match['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $this->command->error("Failed to process match {$match['id']}: {$e->getMessage()}");
                }
            }

            $this->command->info("Tournament {$tournamentId} processed successfully!");

        } catch (\Exception $e) {
            // Check if it's a "tournament not found" error - this might be a validation issue
            if (str_contains($e->getMessage(), 'Tournament not found') ||
                str_contains($e->getMessage(), '404') ||
                str_contains($e->getMessage(), 'TOURNAMENT_NOT')) {

                // Try to verify tournament exists in tournament-service
                $tournamentServiceUrl = config('services.tournament_service.url', env('TOURNAMENT_SERVICE_URL', 'http://tournament-service:8002'));
                $verifyCheck = \Illuminate\Support\Facades\Http::get("{$tournamentServiceUrl}/api/tournaments/{$tournamentId}");

                if ($verifyCheck->status() === 200) {
                    $this->command->warn("Tournament {$tournamentId} exists in tournament-service but match-service cannot validate it. This might mean the tournament has no matches yet.");
                    Log::info("Tournament {$tournamentId} exists but match-service validation failed", [
                        'tournament_id' => $tournamentId,
                        'tournament_service_status' => $verifyCheck->status()
                    ]);
                } else {
                    $this->command->warn("Tournament {$tournamentId} not found in tournament-service, skipping...");
                    Log::info("Tournament {$tournamentId} not found in tournament-service", [
                        'tournament_id' => $tournamentId
                    ]);
                }
                return;
            }

            Log::error("Failed to fetch matches for tournament {$tournamentId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->command->error("Failed to process tournament {$tournamentId}: {$e->getMessage()}");
        }
    }


}
