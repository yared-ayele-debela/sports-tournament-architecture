<?php

namespace App\Services;

class TournamentServiceClient extends ServiceClient
{
    protected function getBaseUrl()
    {
        return env('TOURNAMENT_SERVICE_URL', 'http://localhost:8002');
    }

    public function getTournament($tournamentId)
    {
        
        return $this->request('GET', "/api/tournaments/{$tournamentId}");
    }

    public function validateTournament($tournamentId)
    {
        return $this->request('GET', "/api/tournaments/{$tournamentId}/validate");
    }
}
