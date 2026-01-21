<?php

namespace App\Services\Clients;

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

    public function getTournaments()
    {
        return $this->get("/api/tournaments");
    }

    public function getTournamentTeams($tournamentId)
    {
        return $this->get("/api/tournaments/{$tournamentId}/teams");
    }

    public function getTournamentMatches($tournamentId)
    {
        return $this->get("/api/tournaments/{$tournamentId}/matches");
    }
}
