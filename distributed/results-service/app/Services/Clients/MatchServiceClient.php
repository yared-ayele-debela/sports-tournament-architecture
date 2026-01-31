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
}
