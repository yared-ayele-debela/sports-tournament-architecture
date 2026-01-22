<?php

namespace App\Services\Clients;

use Illuminate\Support\Facades\Log;

class TeamServiceClient extends ServiceClient
{
    public function __construct()
    {
        parent::__construct(config('services.team.url', env('TEAM_SERVICE_URL', 'http://localhost:8003')));
    }

    /**
     * Get team by ID
     */
    public function getTeam(int $id): array
    {
        return $this->get("/api/public/teams/{$id}", [], ['teams'], 600);
    }

    /**
     * Get teams by tournament
     */
    public function getTeamsByTournament(int $tournamentId): array
    {
        return $this->get('/api/public/teams', [
            'tournament_id' => $tournamentId
        ], ['teams', 'tournaments'], 300);
    }

    /**
     * Get team players
     */
    public function getTeamPlayers(int $teamId): array
    {
        return $this->get("/api/public/teams/{$teamId}/squad", [], ['teams', 'players'], 300);
    }

    /**
     * Get team overview
     */
    public function getTeamOverview(int $teamId): array
    {
        return $this->get("/api/public/teams/{$teamId}/overview", [], ['teams'], 300);
    }

    /**
     * Get team matches
     */
    public function getTeamMatches(int $teamId, array $filters = []): array
    {
        return $this->get("/api/public/teams/{$teamId}/matches", $filters, ['teams', 'matches'], 300);
    }

    /**
     * Get head to head record
     */
    public function getHeadToHead(int $teamId, int $opponentId): array
    {
        return $this->get("/api/public/teams/{$teamId}/head-to-head", ['opponent_id' => $opponentId], ['teams'], 300);
    }

    /**
     * Get player details
     */
    public function getPlayer(int $playerId): array
    {
        return $this->get("/api/public/players/{$playerId}", [], ['players'], 600);
    }

    /**
     * Get player statistics
     */
    public function getPlayerStatistics(int $playerId, int $tournamentId = null): array
    {
        $params = [];
        if ($tournamentId) {
            $params['tournament_id'] = $tournamentId;
        }

        return $this->get("/api/public/players/{$playerId}/statistics", $params, ['players', 'statistics'], 300);
    }

    /**
     * Get team statistics
     */
    public function getTeamStatistics(int $teamId, int $tournamentId = null): array
    {
        $params = [];
        if ($tournamentId) {
            $params['tournament_id'] = $tournamentId;
        }

        return $this->get("/api/public/teams/{$teamId}/statistics", $params, ['teams', 'statistics'], 600);
    }

    /**
     * Search teams
     */
    public function searchTeams(string $query, array $filters = []): array
    {
        return $this->get('/api/public/teams/search', array_merge([
            'q' => $query
        ], $filters), ['teams'], 180);
    }
}
