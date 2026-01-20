<?php

namespace App\Services\Clients;

class TournamentServiceClient extends ServiceClient
{
    public function __construct()
    {
        parent::__construct('http://localhost:8005');
    }

    public function getTournament($tournamentId)
    {
        return $this->get("/api/tournaments/{$tournamentId}");
    }

    public function getVenue($venueId)
    {
        return $this->get("/api/venues/{$venueId}");
    }
}
