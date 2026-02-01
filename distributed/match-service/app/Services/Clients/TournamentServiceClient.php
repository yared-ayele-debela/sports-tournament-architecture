<?php

namespace App\Services\Clients;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TournamentServiceClient extends ServiceClient
{
    public function __construct()
    {
        parent::__construct(env('TOURNAMENT_SERVICE_URL', 'http://localhost:8002'));
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
     * Get public tournament details from Tournament Service with caching.
     *
     * @param int $tournamentId
     * @return array|null
     */
    public function getPublicTournament(int $tournamentId): ?array
    {
        $cacheKey = "public_tournament:{$tournamentId}";
        $cacheTtl = 300; // 5 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($tournamentId) {
            try {
                $response = $this->get("/api/public/tournaments/{$tournamentId}");
                if (isset($response['success']) && $response['success'] && isset($response['data'])) {
                    return $response['data'];
                }
                Log::warning('Tournament Service returned unsuccessful response for public tournament', [
                    'tournament_id' => $tournamentId,
                    'response' => $response
                ]);
                return null;
            } catch (\Exception $e) {
                Log::error('Failed to fetch public tournament from Tournament Service', [
                    'tournament_id' => $tournamentId,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
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
