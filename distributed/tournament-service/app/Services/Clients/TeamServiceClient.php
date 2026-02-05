<?php

namespace App\Services\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class TeamServiceClient
{
    protected Client $httpClient;
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('TEAM_SERVICE_URL', 'http://localhost:8003');
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 5, // Reduced timeout to fail faster
            'connect_timeout' => 2, // Reduced connection timeout
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Get teams by tournament
     */
    public function getTournamentTeams(int $tournamentId): array
    {
        try {
            $headers = [];

            // Forward correlation ID from incoming request
            if (request()->header('X-Request-ID')) {
                $headers['X-Request-ID'] = request()->header('X-Request-ID');
            }

            $response = $this->httpClient->get("/api/public/tournaments/{$tournamentId}/teams", [
                'headers' => $headers,
            ]);
            $responseContent = $response->getBody()->getContents();

            Log::info("Fetched tournament teams from TeamService", [
                'tournament_id' => $tournamentId,
                'response' => $responseContent
            ]);

            $data = json_decode($responseContent, true);

            return [
                'success' => true,
                'data' => $data['data'] ?? [],
                'status' => $response->getStatusCode(),
            ];
        } catch (RequestException $e) {
            // Check if it's a connection/timeout error (service not available)
            $isConnectionError = str_contains($e->getMessage(), 'timed out') ||
                                str_contains($e->getMessage(), 'Connection refused') ||
                                str_contains($e->getMessage(), 'cURL error 28');

            if ($isConnectionError) {
                Log::warning("TeamService is not available (connection/timeout error)", [
                    'tournament_id' => $tournamentId,
                    'error' => $e->getMessage(),
                    'service' => 'TeamService'
                ]);
            } else {
                Log::error("Failed to fetch tournament teams from TeamService", [
                    'tournament_id' => $tournamentId,
                    'error' => $e->getMessage(),
                    'service' => 'TeamService'
                ]);
            }

            return [
                'success' => false,
                'error' => $isConnectionError ? 'Team service unavailable' : $e->getMessage(),
                'status' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : 503,
                'service_unavailable' => $isConnectionError,
            ];
        } catch (\Exception $e) {
            Log::error("Unexpected error fetching tournament teams", [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Service unavailable',
                'status' => 503,
                'service_unavailable' => true,
            ];
        }
    }
}
