<?php

namespace App\Services\Clients;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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

    /**
     * Get public team details from Team Service with caching.
     *
     * @param int $teamId
     * @return array|null
     */
    public function getPublicTeam(int $teamId): ?array
    {
        $cacheKey = "public_team:{$teamId}";
        $cacheTtl = 300; // 5 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($teamId) {
            try {
                $response = $this->get("/api/public/teams/{$teamId}");
                if (isset($response['success']) && $response['success'] && isset($response['data'])) {
                    return $response['data'];
                }
                Log::warning('Team Service returned unsuccessful response for public team', [
                    'team_id' => $teamId,
                    'response' => $response
                ]);
                return null;
            } catch (\Exception $e) {
                Log::error('Failed to fetch public team from Team Service', [
                    'team_id' => $teamId,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }

    /**
     * Get public team players from Team Service with caching.
     *
     * @param int $teamId
     * @return array|null
     */
    public function getPublicTeamPlayers(int $teamId): ?array
    {
        $cacheKey = "public_team:{$teamId}:players";
        $cacheTtl = 600; // 10 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($teamId) {
            try {
                $response = $this->get("/api/public/teams/{$teamId}/players");
                if (isset($response['success']) && $response['success'] && isset($response['data']['players'])) {
                    return $response['data']['players'];
                }
                Log::warning('Team Service returned unsuccessful response for public team players', [
                    'team_id' => $teamId,
                    'response' => $response
                ]);
                return null;
            } catch (\Exception $e) {
                Log::error('Failed to fetch public team players from Team Service', [
                    'team_id' => $teamId,
                    'error' => $e->getMessage()
                ]);
                return null;
            }
        });
    }
}
