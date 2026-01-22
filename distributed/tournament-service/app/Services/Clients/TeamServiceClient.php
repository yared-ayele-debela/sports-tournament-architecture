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
            'timeout' => 10,
            'connect_timeout' => 5,
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
            $response = $this->httpClient->get("/api/tournaments/{$tournamentId}/teams");
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
            Log::error("Failed to fetch tournament teams from TeamService", [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
                'service' => 'TeamService'
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => $e->hasResponse() ? $e->getResponse()->getStatusCode() : 500,
            ];
        } catch (\Exception $e) {
            Log::error("Unexpected error fetching tournament teams", [
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
