<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Clients\ResultsServiceClient;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PublicStandingsController extends Controller
{
    protected ResultsServiceClient $resultsClient;

    public function __construct(ResultsServiceClient $resultsClient)
    {
        $this->resultsClient = $resultsClient;
    }

    /**
     * Get tournament standings
     */
    public function show(Request $request, int $tournamentId): \Illuminate\Http\JsonResponse
    {
        try {
            $cacheKey = "standings_public:{$tournamentId}";
            $cacheTtl = 180; // 3 minutes

            $cached = Cache::remember($cacheKey, $cacheTtl, function () use ($tournamentId) {
                $response = $this->resultsClient->getStandings($tournamentId);

                return [
                    'status' => $response['status'] ?? 500,
                    'body' => $response['data'] ?? null,
                ];
            });

            return response()->json($cached['body'], $cached['status']);
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve standings');
        }
    }

    /**
     * Get standings with team details
     */
    public function withTeams(Request $request, int $tournamentId): \Illuminate\Http\JsonResponse
    {
        try {
            $cacheKey = "standings_with_teams_public:{$tournamentId}";
            $cacheTtl = 300; // 5 minutes

            $data = Cache::remember($cacheKey, $cacheTtl, function () use ($tournamentId) {
                $standingsResponse = $this->resultsClient->getStandings($tournamentId);
                
                if (!$standingsResponse['success']) {
                    return null;
                }

                $standings = $standingsResponse['data'];
                $standingsWithTeams = [];

                foreach ($standings as $standing) {
                    // Fetch team details for each standing
                    $teamResponse = $this->resultsClient->getTeamStatistics($standing['team_id'], $tournamentId);
                    
                    $standingsWithTeams[] = array_merge($standing, [
                        'team_details' => $teamResponse['success'] ? $teamResponse['data'] : null,
                    ]);
                }

                return $standingsWithTeams;
            });

            if (!$data) {
                return ApiResponse::notFound('Standings not found for this tournament');
            }

            return ApiResponse::success($data, 'Standings with team details retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve standings with team details');
        }
    }

    /**
     * Get tournament statistics
     */
    public function statistics(Request $request, int $tournamentId): \Illuminate\Http\JsonResponse
    {
        try {
            $cacheKey = "tournament_statistics_public:{$tournamentId}";
            $cacheTtl = 600; // 10 minutes

            $cached = Cache::remember($cacheKey, $cacheTtl, function () use ($tournamentId) {
                $response = $this->resultsClient->getTournamentStatistics($tournamentId);

                return [
                    'status' => $response['status'] ?? 500,
                    'body' => $response['data'] ?? null,
                ];
            });

            return response()->json($cached['body'], $cached['status']);
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve tournament statistics');
        }
    }

    /**
     * Get top scorers for tournament
     */
    public function topScorers(Request $request, int $tournamentId): \Illuminate\Http\JsonResponse
    {
        try {
            $limit = min($request->get('limit', 10), 50); // Max 50 players
            
            $cacheKey = "top_scorers_public:{$tournamentId}:{$limit}";
            $cacheTtl = 600; // 10 minutes
            
            $cached = Cache::remember($cacheKey, $cacheTtl, function () use ($tournamentId, $limit) {
                $response = $this->resultsClient->getTopScorers($tournamentId, $limit);

                return [
                    'status' => $response['status'] ?? 500,
                    'body' => $response['data'] ?? null,
                ];
            });

            return response()->json($cached['body'], $cached['status']);
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve top scorers');
        }
    }
}
