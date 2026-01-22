<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Aggregators\TeamAggregator;
use App\Services\Clients\TeamServiceClient;
use App\Services\Clients\MatchServiceClient;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PublicTeamController extends Controller
{
    protected TeamAggregator $teamAggregator;
    protected TeamServiceClient $teamClient;
    protected MatchServiceClient $matchClient;

    public function __construct(
        TeamAggregator $teamAggregator,
        TeamServiceClient $teamClient,
        MatchServiceClient $matchClient
    ) {
        $this->teamAggregator = $teamAggregator;
        $this->teamClient = $teamClient;
        $this->matchClient = $matchClient;
    }

    /**
     * Get team profile
     */
    public function show(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $cacheKey = "team_profile_public:{$id}";
            $cacheTtl = 900; // 15 minutes

            $data = Cache::remember($cacheKey, $cacheTtl, function () use ($id) {
                $response = $this->teamClient->getTeam($id);
                
                if (!$response['success']) {
                    return null;
                }

                return $response['data'];
            });

            if (!$data) {
                return ApiResponse::notFound('Team not found');
            }

            return ApiResponse::success($data, 'Team profile retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve team profile');
        }
    }

    /**
     * Get team overview
     */
    public function overview(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'tournament_id' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $tournamentId = $request->get('tournament_id');
            $cacheKey = "team_overview_public:{$id}:" . ($tournamentId ?? 'all');
            $cacheTtl = 600; // 10 minutes

            $data = Cache::remember($cacheKey, $cacheTtl, function () use ($id, $tournamentId) {
                $response = $this->teamAggregator->getTeamOverview($id, $tournamentId);
                
                if (!$response['success']) {
                    return null;
                }

                return $response['data'];
            });

            if (!$data) {
                return ApiResponse::notFound('Team not found');
            }

            return ApiResponse::success($data, 'Team overview retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve team overview');
        }
    }

    /**
     * Get team squad
     */
    public function squad(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $cacheKey = "team_squad_public:{$id}";
            $cacheTtl = 1200; // 20 minutes

            $cached = Cache::remember($cacheKey, $cacheTtl, function () use ($id) {
                $response = $this->teamClient->getTeamPlayers($id);

                return [
                    'status' => $response['status'] ?? 500,
                    'body' => $response['data'] ?? null,
                ];
            });

            return response()->json($cached['body'], $cached['status']);
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve team squad');
        }
    }

    /**
     * Get team matches
     */
    public function matches(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'nullable|string|in:upcoming,completed',
                'tournament_id' => 'nullable|integer|min:1',
                'limit' => 'nullable|integer|min:1|max:50',
                'page' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $filters = $request->only(['status', 'tournament_id', 'limit', 'page']);

            $cacheKey = "team_matches_public:{$id}:" . md5(serialize($filters));
            $cacheTtl = 300;

            $cached = Cache::remember($cacheKey, $cacheTtl, function () use ($filters, $id) {
                $response = $this->teamClient->getTeamMatches($id, $filters);

                return [
                    'status' => $response['status'] ?? 500,
                    'body' => $response['data'] ?? null,
                ];
            });

            return response()->json($cached['body'], $cached['status']);
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve team matches');
        }
    }

    /**
     * Get team statistics
     */
    public function statistics(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'tournament_id' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $tournamentId = $request->get('tournament_id');
            $cacheKey = "team_statistics_public:{$id}:" . ($tournamentId ?? 'all');
            $cacheTtl = 600; // 10 minutes

            $data = Cache::remember($cacheKey, $cacheTtl, function () use ($id, $tournamentId) {
                $response = $this->teamClient->getTeamStatistics($id, $tournamentId);
                
                if (!$response['success']) {
                    return null;
                }

                return $response['data'];
            });

            if (!$data) {
                return ApiResponse::notFound('Team statistics not found');
            }

            return ApiResponse::success($data, 'Team statistics retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve team statistics');
        }
    }

    /**
     * Get head-to-head record
     */
    public function headToHead(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'opponent_id' => 'required|integer|min:1|different:id',
                'tournament_id' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $opponentId = $request->get('opponent_id');
            $tournamentId = $request->get('tournament_id');
            $cacheKey = "head_to_head_public:{$id}:{$opponentId}:" . ($tournamentId ?? 'all');
            $cacheTtl = 1800; // 30 minutes

            $data = Cache::remember($cacheKey, $cacheTtl, function () use ($id, $opponentId, $tournamentId) {
                $response = $this->teamAggregator->getHeadToHead($id, $opponentId, $tournamentId);
                
                if (!$response['success']) {
                    return null;
                }

                return $response['data'];
            });

            if (!$data) {
                return ApiResponse::notFound('Head-to-head data not found');
            }

            return ApiResponse::success($data, 'Head-to-head data retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve head-to-head data');
        }
    }
}
