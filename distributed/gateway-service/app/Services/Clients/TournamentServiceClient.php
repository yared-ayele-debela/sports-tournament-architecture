<?php

namespace App\Services\Clients;

use Illuminate\Support\Facades\Log;

class TournamentServiceClient extends ServiceClient
{
    public function __construct()
    {
        parent::__construct(config('services.tournament.url', env('TOURNAMENT_SERVICE_URL', 'http://localhost:8002')));
    }

    /**
     * Get tournament by ID
     */
    public function getTournament(int $id): array
    {
        Log::info("Fetching tournament {$id}");
        return $this->get("/api/tournaments/{$id}", [], ['tournaments'], 600);
    }

    /**
     * Get tournaments with optional filters
     */
    public function getTournaments(array $filters = []): array
    {
        return $this->get('/api/tournaments', $filters, ['tournaments'], 300);
    }

    /**
     * Get venue by ID
     */
    public function getVenue(int $id): array
    {
        return $this->get("/api/venues/{$id}", [], ['venues'], 600);
    }

    /**
     * Get tournament venues
     */
    public function getTournamentVenues(int $tournamentId): array
    {
        return $this->get("/api/tournaments/{$tournamentId}/venues", [], ['tournaments', 'venues'], 300);
    }

    /**
     * Get tournament matches
     */
    public function getTournamentMatches(int $tournamentId, array $filters = []): array
    {
        Log::info("Fetching tournament matches for tournament {$tournamentId}");
        return $this->get("/api/tournaments/{$tournamentId}/matches", $filters, ['tournaments', 'matches'], 180);
    }

    /**
     * Get tournament teams
     */
    public function getTournamentTeams(int $tournamentId): array
    {
        return $this->get("/api/tournaments/{$tournamentId}/teams", [], ['tournaments', 'teams'], 300);
    }

    /**
     * Get tournament standings
     */
    public function getTournamentStandings(int $id): array
    {
        return $this->get("/api/tournaments/{$id}/standings", [], ['tournaments', 'standings'], 180);
    }

    /**
     * Get tournament statistics
     */
    public function getTournamentStatistics(int $id): array
    {
        return $this->get("/api/tournaments/{$id}/statistics", [], ['tournaments', 'statistics'], 600);
    }

    /**
     * Get tournament overview
     */
    public function getTournamentOverview(int $id): array
    {
        return $this->get("/api/tournaments/{$id}/overview", [], ['tournaments'], 300);
    }
}
