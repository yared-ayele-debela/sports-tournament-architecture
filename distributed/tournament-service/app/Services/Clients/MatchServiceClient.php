<?php

namespace App\Services\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class MatchServiceClient
{
    protected Client $httpClient;
    protected string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('MATCH_SERVICE_URL', 'http://localhost:8004');
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
     * Get matches by tournament
     */
    public function getTournamentMatches(int $tournamentId, array $filters = []): array
    {
        try {
            $response = $this->httpClient->get("/api/public/tournaments/{$tournamentId}/matches", [
                'query' => $filters
            ]);
            $responseContent = $response->getBody()->getContents();
            Log::info("Fetched tournament matches from MatchService", [
                'tournament_id' => $tournamentId,
                'response' => $responseContent
            ]);

            $data = json_decode($responseContent, true);

            return [
                'success' => true,
                'data' => $data,
                'status' => $response->getStatusCode(),
            ];
        } catch (RequestException $e) {
            Log::error("Failed to fetch tournament matches from MatchService", [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
                'service' => 'MatchService'
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500,
            ];
        } catch (\Exception $e) {
            Log::error("Unexpected error fetching tournament matches", [
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
