<?php

namespace App\Services\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class ResultsServiceClient
{
    protected Client $httpClient;
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('RESULTS_SERVICE_URL', 'http://results-service:8005');
        $this->httpClient = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 10,
            'connect_timeout' => 5,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Get tournament standings
     */
    public function getStandings(int $tournamentId): array
    {
        try {
            $headers = [];

            // Forward correlation ID from incoming request
            if (request()->header('X-Request-ID')) {
                $headers['X-Request-ID'] = request()->header('X-Request-ID');
            }

            $response = $this->httpClient->get("/api/tournaments/{$tournamentId}/standings", [
                'headers' => $headers,
            ]);
            $responseContent = $response->getBody()->getContents();

            Log::info("Fetched tournament standings from ResultsService", [
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
            Log::error("Failed to fetch tournament standings from ResultsService", [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
                'service' => 'ResultsService'
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500,
            ];
        } catch (\Exception $e) {
            Log::error("Unexpected error fetching tournament standings", [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Service unavailable',
                'status' => 500,
            ];
        }
    }

    /**
     * Get tournament statistics
     */
    public function getTournamentStatistics(int $tournamentId): array
    {
        try {
            $headers = [];

            // Forward correlation ID from incoming request
            if (request()->header('X-Request-ID')) {
                $headers['X-Request-ID'] = request()->header('X-Request-ID');
            }

            $response = $this->httpClient->get("/api/tournaments/{$tournamentId}/statistics", [
                'headers' => $headers,
            ]);
            $responseContent = $response->getBody()->getContents();

            Log::info("Fetched tournament statistics from ResultsService", [
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
            Log::error("Failed to fetch tournament statistics from ResultsService", [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
                'service' => 'ResultsService'
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500,
            ];
        } catch (\Exception $e) {
            Log::error("Unexpected error fetching tournament statistics", [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Service unavailable',
                'status' => 500,
            ];
        }
    }

    /**
     * Get top scorers for tournament
     */
    public function getTopScorers(int $tournamentId, int $limit = 10): array
    {
        try {
            $headers = [];

            // Forward correlation ID from incoming request
            if (request()->header('X-Request-ID')) {
                $headers['X-Request-ID'] = request()->header('X-Request-ID');
            }

            $response = $this->httpClient->get("/api/tournaments/{$tournamentId}/top-scorers", [
                'query' => ['limit' => $limit],
                'headers' => $headers,
            ]);
            $responseContent = $response->getBody()->getContents();

            Log::info("Fetched top scorers from ResultsService", [
                'tournament_id' => $tournamentId,
                'limit' => $limit,
                'response' => $responseContent
            ]);

            $data = json_decode($responseContent, true);

            return [
                'success' => true,
                'data' => $data['data'] ?? [],
                'status' => $response->getStatusCode(),
            ];
        } catch (RequestException $e) {
            Log::error("Failed to fetch top scorers from ResultsService", [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
                'service' => 'ResultsService'
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500,
            ];
        } catch (\Exception $e) {
            Log::error("Unexpected error fetching top scorers", [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Service unavailable',
                'status' => 500,
            ];
        }
    }
}
