<?php

namespace App\Services\Clients;

class TeamServiceClient extends ServiceClient
{
    public function __construct()
    {
        parent::__construct('http://localhost:8002');
    }

    public function getTeam($teamId)
    {
        return $this->get("/api/teams/{$teamId}");
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

    public function getReferee($refereeId)
    {
        return $this->get("/api/referees/{$refereeId}");
    }

    public function validateReferee($refereeId)
    {
        return $this->get("/api/referees/{$refereeId}/validate");
    }

    public function getTournamentTeams($tournamentId)
    {
        return $this->get("/api/tournaments/{$tournamentId}/teams");
    }
}
