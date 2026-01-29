<?php

namespace App\Services\Clients;

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

    public function getCompletedMatchesWithoutAuth($tournamentId)
    {
        try {
            $response = $this->client->get("/api/tournaments/{$tournamentId}/matches?status=completed");
            return json_decode($response->getBody()->getContents(), true);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Service request failed: {$e->getMessage()}", [
                'service' => class_basename($this),
                'endpoint' => "/api/tournaments/{$tournamentId}/matches?status=completed",
            ]);
            return null;
        }
    }
}
