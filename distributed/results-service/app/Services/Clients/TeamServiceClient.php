<?php

namespace App\Services\Clients;

class TeamServiceClient extends ServiceClient
{
    public function __construct()
    {
        parent::__construct(env('TEAM_SERVICE_URL', 'http://localhost:8003'));
    }

    public function getTeam($teamId)
    {
        return $this->get("/api/teams/{$teamId}");
    }

    public function getTeamsByTournament($tournamentId)
    {
        return $this->get("/api/tournaments/{$tournamentId}/teams");
    }

    public function getTeamPlayers($teamId)
    {
        return $this->get("/api/teams/{$teamId}/players");
    }

    public function validatePlayer($playerId, $teamId)
    {
        return $this->get("/api/teams/{$teamId}/players/{$playerId}/validate");
    }

    public function getPlayer($playerId)
    {
        return $this->get("/api/players/{$playerId}");
    }
}
