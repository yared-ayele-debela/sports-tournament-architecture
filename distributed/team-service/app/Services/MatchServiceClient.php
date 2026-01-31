<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class MatchServiceClient extends ServiceClient
{
    protected function getBaseUrl()
    {
        return env('MATCH_SERVICE_URL', 'http://localhost:8004');
    }

    /**
     * Get matches for a team
     *
     * @param int $teamId
     * @param array $filters Optional filters (status, limit, etc.)
     * @return array|null Match data or null on failure
     */
    public function getTeamMatches(int $teamId, array $filters = []): ?array
    {
        try {
            $queryParams = array_merge(['team_id' => $teamId], $filters);
            $queryString = http_build_query($queryParams);
            
            $response = $this->request('GET', "/api/public/matches?{$queryString}");
            
            if (isset($response['success']) && $response['success'] && isset($response['data'])) {
                return $response['data'];
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('Failed to fetch team matches from match service', [
                'team_id' => $teamId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
