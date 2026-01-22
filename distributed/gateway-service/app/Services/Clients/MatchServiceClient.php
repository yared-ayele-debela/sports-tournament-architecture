<?php

namespace App\Services\Clients;

use Illuminate\Support\Facades\Log;

class MatchServiceClient extends ServiceClient
{
    public function __construct()
    {
        parent::__construct(config('services.match.url', env('MATCH_SERVICE_URL', 'http://localhost:8004')));
    }

    /**
     * Get match by ID
     */
    public function getMatch(int $id): array
    {
        return $this->get("/api/public/matches/{$id}/public", [], ['matches'], 600);
    }

    /**
     * Get matches with optional filters
     */
    public function getMatches(array $filters = []): array
    {
        return $this->get('/api/public/matches', $filters, ['matches'], 180);
    }

    /**
     * Get match events
     */
    public function getMatchEvents(int $matchId): array
    {
        return $this->get("/api/public/matches/{$matchId}/events/public", [], ['matches', 'events'], 300);
    }

    /**
     * Get live matches
     */
    public function getLiveMatches(): array
    {
        return $this->get('/api/public/matches/live', [], ['matches'], 60); // Cache for 1 minute for live data
    }

    /**
     * Get matches by tournament
     */
    public function getTournamentMatches(int $tournamentId, array $filters = []): array
    {
        return $this->get('/api/public/matches', array_merge([
            'tournament_id' => $tournamentId
        ], $filters), ['matches', 'tournaments'], 180);
    }

    /**
     * Get matches by team
     */
    public function getTeamMatches(int $teamId, array $filters = []): array
    {
        return $this->get('/api/public/matches', array_merge([
            'team_id' => $teamId
        ], $filters), ['matches', 'teams'], 180);
    }

    /**
     * Get upcoming matches
     */
    public function getUpcomingMatches(array $filters = []): array
    {
        return $this->get('/api/public/matches/upcoming', $filters, ['matches'], 120);
    }

    /**
     * Get completed matches
     */
    public function getCompletedMatches(array $filters = []): array
    {
        return $this->get('/api/public/matches/completed', $filters, ['matches'], 300);
    }

    /**
     * Get match statistics
     */
    public function getMatchStatistics(int $matchId): array
    {
        return $this->get("/api/public/matches/{$matchId}/statistics", [], ['matches', 'statistics'], 600);
    }

    /**
     * Get match lineups
     */
    public function getMatchLineups(int $matchId): array
    {
        return $this->get("/api/public/matches/{$matchId}/lineups", [], ['matches', 'lineups'], 300);
    }

    /**
     * Get matches by date
     */
    public function getMatchesByDate(string $date, array $filters = []): array
    {
        return $this->get("/api/public/matches/date/{$date}", $filters, ['matches'], 300);
    }
}
