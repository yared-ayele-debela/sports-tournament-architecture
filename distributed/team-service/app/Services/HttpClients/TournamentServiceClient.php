<?php

namespace App\Services\HttpClients;

use Illuminate\Support\Facades\Log;

class TournamentServiceClient extends ServiceClient
{
    /**
     * Create a new Tournament Service client instance.
     */
    public function __construct(?string $jwtToken = null)
    {
        $tournamentServiceUrl = config('services.tournament.url', env('TOURNAMENT_SERVICE_URL', 'http://tournament-service:8000'));
        parent::__construct($tournamentServiceUrl, $jwtToken);
    }

    /**
     * Get tournament details by ID.
     *
     * @param int $tournamentId
     * @return array|null Tournament data array or null if not found
     */
    public function getTournament(int $tournamentId): ?array
    {
        try {
            $response = $this->get("/api/tournaments/{$tournamentId}");

            if (!$response) {
                Log::error("Failed to get tournament {$tournamentId}: No response from tournament service");
                return null;
            }

            $success = $response['success'] ?? false;
            $tournamentData = $response['data'] ?? null;

            if (!$success || !$tournamentData) {
                Log::warning("Tournament not found or access denied", [
                    'tournament_id' => $tournamentId,
                    'response' => $response
                ]);
                return null;
            }

            Log::info("Successfully retrieved tournament data", [
                'tournament_id' => $tournamentId,
                'tournament_name' => $tournamentData['name'] ?? 'Unknown'
            ]);

            return $tournamentData;
        } catch (\Exception $e) {
            Log::error("Exception while getting tournament data", [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Validate if a tournament exists and is accessible.
     *
     * @param int $tournamentId
     * @return bool True if tournament exists and is accessible, false otherwise
     */
    public function validateTournament(int $tournamentId): bool
    {
        try {
            $response = $this->post('/api/tournaments/validate', [
                'tournament_id' => $tournamentId
            ]);

            if (!$response) {
                Log::error("Failed to validate tournament {$tournamentId}: No response from tournament service");
                return false;
            }

            $success = $response['success'] ?? false;
            $exists = $response['exists'] ?? false;

            if (!$success || !$exists) {
                Log::warning("Tournament validation failed", [
                    'tournament_id' => $tournamentId,
                    'response' => $response
                ]);
                return false;
            }

            Log::info("Tournament validation successful", [
                'tournament_id' => $tournamentId,
                'response' => $response
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Exception during tournament validation", [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get all tournaments.
     *
     * @return array Array of tournaments
     */
    public function getAllTournaments(): array
    {
        try {
            $response = $this->get('/api/tournaments');

            if (!$response) {
                Log::error("Failed to get tournaments: No response from tournament service");
                return [];
            }

            $success = $response['success'] ?? false;
            $tournaments = $response['data'] ?? [];

            if (!$success) {
                Log::warning("Failed to get tournaments", [
                    'response' => $response
                ]);
                return [];
            }

            Log::info("Successfully retrieved tournaments", [
                'tournament_count' => count($tournaments)
            ]);

            return $tournaments;
        } catch (\Exception $e) {
            Log::error("Exception while getting tournaments", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Get tournament venues.
     *
     * @param int $tournamentId
     * @return array Array of venues for the tournament
     */
    public function getTournamentVenues(int $tournamentId): array
    {
        try {
            $response = $this->get("/api/tournaments/{$tournamentId}/venues");

            if (!$response) {
                Log::error("Failed to get venues for tournament {$tournamentId}: No response from tournament service");
                return [];
            }

            $success = $response['success'] ?? false;
            $venues = $response['data'] ?? [];

            if (!$success) {
                Log::warning("Failed to get tournament venues", [
                    'tournament_id' => $tournamentId,
                    'response' => $response
                ]);
                return [];
            }

            Log::info("Successfully retrieved tournament venues", [
                'tournament_id' => $tournamentId,
                'venue_count' => count($venues)
            ]);

            return $venues;
        } catch (\Exception $e) {
            Log::error("Exception while getting tournament venues", [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Get tournament sports.
     *
     * @param int $tournamentId
     * @return array Array of sports for the tournament
     */
    public function getTournamentSports(int $tournamentId): array
    {
        try {
            $response = $this->get("/api/tournaments/{$tournamentId}/sports");

            if (!$response) {
                Log::error("Failed to get sports for tournament {$tournamentId}: No response from tournament service");
                return [];
            }

            $success = $response['success'] ?? false;
            $sports = $response['data'] ?? [];

            if (!$success) {
                Log::warning("Failed to get tournament sports", [
                    'tournament_id' => $tournamentId,
                    'response' => $response
                ]);
                return [];
            }

            Log::info("Successfully retrieved tournament sports", [
                'tournament_id' => $tournamentId,
                'sport_count' => count($sports)
            ]);

            return $sports;
        } catch (\Exception $e) {
            Log::error("Exception while getting tournament sports", [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Test connection to tournament service.
     *
     * @return bool True if connection is successful
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->get('/api/health');
            
            if (!$response) {
                return false;
            }

            return $response['success'] ?? false;
        } catch (\Exception $e) {
            Log::error("Tournament service connection test failed", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
