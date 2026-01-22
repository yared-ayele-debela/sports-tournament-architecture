<?php

namespace App\Services\Clients;

class ResultsServiceClient extends ServiceClient
{
    public function __construct()
    {
        parent::__construct(config('services.results.url', env('RESULTS_SERVICE_URL', 'http://localhost:8005')));
    }

    /**
     * Get tournament standings
     */
    public function getStandings(int $tournamentId): array
    {
        return $this->get("/api/tournaments/{$tournamentId}/standings", [], ['standings', 'tournaments'], 180);
    }

    /**
     * Get tournament results
     */
    public function getTournamentResults(int $tournamentId, array $filters = []): array
    {
        return $this->get("/api/tournaments/{$tournamentId}/results", $filters, ['results', 'tournaments'], 180);
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

        return $this->get("/api/teams/{$teamId}/statistics", $params, ['statistics', 'teams'], 300);
    }

    /**
     * Get match result
     */
    public function getMatchResult(int $matchId): array
    {
        return $this->get("/api/match-results/{$matchId}", [], ['match-results'], 600);
    }

    /**
     * Get tournament statistics
     */
    public function getTournamentStatistics(int $tournamentId): array
    {
        return $this->get("/api/tournaments/{$tournamentId}/statistics", [], ['statistics', 'tournaments'], 300);
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

        return $this->get("/api/players/{$playerId}/statistics", $params, ['statistics', 'players'], 300);
    }

    /**
     * Get top scorers for tournament
     */
    public function getTopScorers(int $tournamentId, int $limit = 10): array
    {
        return $this->get("/api/tournaments/{$tournamentId}/top-scorers", [
            'limit' => $limit
        ], ['statistics', 'tournaments'], 300);
    }

    /**
     * Get team form (last N matches)
     */
    public function getTeamForm(int $teamId, int $limit = 5): array
    {
        return $this->get("/api/teams/{$teamId}/form", [
            'limit' => $limit
        ], ['statistics', 'teams'], 180);
    }

    /**
     * Get head-to-head record between two teams
     */
    public function getHeadToHead(int $team1Id, int $team2Id, int $tournamentId = null): array
    {
        $params = [
            'team1_id' => $team1Id,
            'team2_id' => $team2Id
        ];
        
        if ($tournamentId) {
            $params['tournament_id'] = $tournamentId;
        }

        return $this->get("/api/head-to-head", $params, ['statistics', 'teams'], 300);
    }
}
