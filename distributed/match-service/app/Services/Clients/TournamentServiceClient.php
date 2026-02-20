<?php

namespace App\Services\Clients;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TournamentServiceClient extends ServiceClient
{
    public function __construct()
    {
        parent::__construct(env('TOURNAMENT_SERVICE_URL', 'http://tournament-service:8002'));
    }

    public function getTournament($tournamentId)
    {
        return $this->get("/api/tournaments/{$tournamentId}");
    }

    public function getVenue($venueId)
    {
        return $this->get("/api/venues/{$venueId}");
    }

    /**
     * Get public tournament details from Tournament Service with caching and retry logic.
     *
     * @param int $tournamentId
     * @return array|null
     */
    public function getPublicTournament(int $tournamentId): ?array
    {
        $cacheKey = "public_tournament:{$tournamentId}";
        $cacheTtl = 300; // 5 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($tournamentId) {
            $maxRetries = 3;
            $retryDelay = 1; // seconds
            
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    $response = $this->get("/api/public/tournaments/{$tournamentId}");
                    if (isset($response['success']) && $response['success'] && isset($response['data'])) {
                        return $response['data'];
                    }
                    
                    // If it's a 404, don't retry - clear cache and return null
                    if (isset($response['error_code']) && $response['error_code'] === 'TOURNAMENT_NOT_FOUND') {
                        Log::warning('Tournament not found in Tournament Service', [
                            'tournament_id' => $tournamentId,
                            'response' => $response
                        ]);
                        // Clear cache to avoid caching null results
                        Cache::forget($cacheKey);
                        return null;
                    }
                    
                    // For other errors, retry
                    if ($attempt < $maxRetries) {
                        Log::warning('Tournament Service returned unsuccessful response, retrying', [
                            'tournament_id' => $tournamentId,
                            'attempt' => $attempt,
                            'max_retries' => $maxRetries,
                            'response' => $response
                        ]);
                        sleep($retryDelay);
                        continue;
                    }
                    
                    Log::warning('Tournament Service returned unsuccessful response after all retries', [
                        'tournament_id' => $tournamentId,
                        'response' => $response
                    ]);
                    // Clear cache on final failure
                    Cache::forget($cacheKey);
                    return null;
                } catch (\Exception $e) {
                    // Retry on connection errors
                    if ($attempt < $maxRetries) {
                        Log::warning('Failed to fetch public tournament, retrying', [
                            'tournament_id' => $tournamentId,
                            'attempt' => $attempt,
                            'max_retries' => $maxRetries,
                            'error' => $e->getMessage()
                        ]);
                        sleep($retryDelay);
                        continue;
                    }
                    
                    Log::error('Failed to fetch public tournament from Tournament Service after all retries', [
                        'tournament_id' => $tournamentId,
                        'error' => $e->getMessage()
                    ]);
                    // Clear cache on final failure
                    Cache::forget($cacheKey);
                    return null;
                }
            }
            
            return null;
        });
    }

    /**
     * Get public venue details from Tournament Service with caching.
     *
     * @param int $venueId
     * @return array|null
     */
    public function getPublicVenue(int $venueId): ?array
    {
        $cacheKey = "public_venue:{$venueId}";
        $cacheTtl = 3600; // 1 hour

        return Cache::remember($cacheKey, $cacheTtl, function () use ($venueId) {
            try {
                $response = $this->get("/api/public/venues/{$venueId}");
                if (isset($response['success']) && $response['success'] && isset($response['data'])) {
                    return $response['data'];
                }
                Log::warning('Tournament Service returned unsuccessful response for public venue', [
                    'venue_id' => $venueId,
                    'response' => $response
                ]);
                return null;
            } catch (\Exception $e) {
                Log::error('Failed to fetch public venue from Tournament Service', [
                    'venue_id' => $venueId,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }
}
