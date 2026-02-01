<?php

namespace App\Services\Clients;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MatchServiceClient extends ServiceClient
{
    public function __construct()
    {
        parent::__construct(env('MATCH_SERVICE_URL', 'http://localhost:8004'));
    }

    public function getMatch($matchId)
    {
        return $this->get("/api/matches/{$matchId}");
    }

    public function getCompletedMatches($tournamentId)
    {
        Log::info("Fetching completed matches for tournament {$tournamentId}");
        $response = $this->get("/api/public/tournaments/{$tournamentId}/matches?status=completed");
        
        // Handle paginated response - extract data if paginated
        if (is_array($response) && isset($response['data'])) {
            return $response['data'];
        }
        
        return $response;
    }

    /**
     * @param int $tournamentId
     * @return array
     * @throws \App\Exceptions\ServiceRequestException
     * @throws \App\Exceptions\ServiceUnavailableException
     */
    public function getCompletedMatchesWithoutAuth($tournamentId)
    {
        // Use the parent get method which throws exceptions
        return $this->get("/api/tournaments/{$tournamentId}/matches", ['status' => 'completed']);
    }

    /**
     * Get public tournament matches from Match Service with caching.
     *
     * @param int $tournamentId
     * @param array $filters
     * @return array|null
     */
    public function getPublicTournamentMatches(int $tournamentId, array $filters = []): ?array
    {
        $cacheKey = "public_tournament:{$tournamentId}:matches:" . md5(serialize($filters));
        $cacheTtl = 300; // 5 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($tournamentId, $filters) {
            try {
                $queryString = http_build_query($filters);
                $endpoint = "/api/public/tournaments/{$tournamentId}/matches";
                if (!empty($queryString)) {
                    $endpoint .= "?{$queryString}";
                }
                
                $response = $this->get($endpoint);
                if (isset($response['success']) && $response['success'] && isset($response['data'])) {
                    return $response['data'];
                }
                Log::warning('Match Service returned unsuccessful response for public tournament matches', [
                    'tournament_id' => $tournamentId,
                    'response' => $response
                ]);
                return null;
            } catch (\Exception $e) {
                Log::error('Failed to fetch public tournament matches from Match Service', [
                    'tournament_id' => $tournamentId,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    /**
     * Get match events for a specific match from Match Service with caching.
     *
     * @param int $matchId
     * @return array|null
     */
    public function getPublicMatchEvents(int $matchId): ?array
    {
        $cacheKey = "public_match:{$matchId}:events";
        $cacheTtl = 300; // 5 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($matchId) {
            try {
                $response = $this->get("/api/public/matches/{$matchId}/events");
                if (isset($response['success']) && $response['success'] && isset($response['data']['events'])) {
                    return $response['data']['events'];
                }
                Log::warning('Match Service returned unsuccessful response for public match events', [
                    'match_id' => $matchId,
                    'response' => $response
                ]);
                return null;
            } catch (\Exception $e) {
                Log::error('Failed to fetch public match events from Match Service', [
                    'match_id' => $matchId,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }
}
