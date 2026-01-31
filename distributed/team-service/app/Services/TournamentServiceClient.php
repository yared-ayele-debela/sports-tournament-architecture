<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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

    /**
     * Get public tournament details (with cache)
     * Used by public API endpoints to verify tournament exists
     *
     * @param int $tournamentId
     * @return array|null Tournament data or null if not found
     */
    public function getPublicTournament(int $tournamentId): ?array
    {
        $cacheKey = "tournament_service:public:tournament:{$tournamentId}";
        $cacheTtl = 300; // 5 minutes

        return Cache::remember($cacheKey, $cacheTtl, function () use ($tournamentId) {
            try {
                $response = $this->request('GET', "/api/public/tournaments/{$tournamentId}");

                if (isset($response['success']) && $response['success'] && isset($response['data'])) {
                    return $response['data'];
                }

                return null;
            } catch (\Exception $e) {
                Log::warning('Failed to fetch public tournament', [
                    'tournament_id' => $tournamentId,
                    'error' => $e->getMessage(),
                ]);
                return null;
            }
        });
    }
}
