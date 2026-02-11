<?php

namespace App\Services\Clients;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MatchServiceClient extends ServiceClient
{
    public function __construct()
    {
        parent::__construct(config('services.match_service.url', env('MATCH_SERVICE_URL', 'http://match-service:8004')));
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
        // Use the public endpoint which doesn't require authentication
        // Create a temporary client with longer timeout for seeder operations
        $tempClient = new \GuzzleHttp\Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 30, // 30 seconds for seeder
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        try {
            $response = $tempClient->get("/api/public/tournaments/{$tournamentId}/matches", [
                'query' => ['status' => 'completed'],
            ]);

            $body = $response->getBody()->getContents();
            $data = json_decode($body, true);

            // Handle JSON decode errors
            if (json_last_error() !== JSON_ERROR_NONE) {
                \Illuminate\Support\Facades\Log::error("Failed to decode JSON response", [
                    'error' => json_last_error_msg(),
                    'body' => substr($body, 0, 500)
                ]);
                throw new \App\Exceptions\ServiceRequestException(
                    'Invalid JSON response from service',
                    'MatchServiceClient',
                    $response->getStatusCode(),
                    ['endpoint' => "/api/public/tournaments/{$tournamentId}/matches"]
                );
            }

            // Ensure data is an array
            if (!is_array($data)) {
                \Illuminate\Support\Facades\Log::error("Response is not an array", [
                    'data_type' => gettype($data),
                    'data' => $data
                ]);
                throw new \App\Exceptions\ServiceRequestException(
                    'Invalid response format from service',
                    'MatchServiceClient',
                    $response->getStatusCode(),
                    ['endpoint' => "/api/public/tournaments/{$tournamentId}/matches"]
                );
            }

            // Check if response indicates failure
            if (isset($data['success']) && !$data['success']) {
                throw new \App\Exceptions\ServiceRequestException(
                    $data['message'] ?? 'Service request failed',
                    'MatchServiceClient',
                    $response->getStatusCode(),
                    ['endpoint' => "/api/public/tournaments/{$tournamentId}/matches", 'response' => $data]
                );
            }

            return $data;
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            \Illuminate\Support\Facades\Log::error("Service connection failed: {$e->getMessage()}", [
                'service' => 'MatchServiceClient',
                'endpoint' => "/api/public/tournaments/{$tournamentId}/matches",
            ]);
            throw new \App\Exceptions\ServiceUnavailableException(
                "Unable to connect to service: {$e->getMessage()}",
                'MatchServiceClient',
                ['endpoint' => "/api/public/tournaments/{$tournamentId}/matches"],
                $e
            );
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            \Illuminate\Support\Facades\Log::error("Service request failed: {$e->getMessage()}", [
                'service' => 'MatchServiceClient',
                'endpoint' => "/api/public/tournaments/{$tournamentId}/matches",
                'status_code' => $statusCode,
            ]);
            throw new \App\Exceptions\ServiceUnavailableException(
                "Service unavailable: {$e->getMessage()}",
                'MatchServiceClient',
                ['endpoint' => "/api/public/tournaments/{$tournamentId}/matches", 'status_code' => $statusCode],
                $e
            );
        }
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
