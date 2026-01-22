<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Aggregators\TournamentAggregator;
use App\Services\Clients\TournamentServiceClient;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PublicTournamentController extends Controller
{
    protected TournamentAggregator $tournamentAggregator;
    protected TournamentServiceClient $tournamentClient;

    public function __construct(
        TournamentAggregator $tournamentAggregator,
        TournamentServiceClient $tournamentClient
    ) {
        $this->tournamentAggregator = $tournamentAggregator;
        $this->tournamentClient = $tournamentClient;
    }

    /**
     * List tournaments with optional filters
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'nullable|string|in:upcoming,ongoing,completed',
                'page' => 'nullable|integer|min:1',
                'limit' => 'nullable|integer|min:1|max:100',
                'sort' => 'nullable|string|in:name,start_date,end_date',
                'order' => 'nullable|string|in:asc,desc',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $filters = $request->only(['status', 'page', 'limit', 'sort', 'order']);
            $cacheKey = 'tournaments_list:' . md5(serialize($filters));
            $cacheTtl = 300; // 5 minutes

            $cached = Cache::remember($cacheKey, $cacheTtl, function () use ($filters) {
                $response = $this->tournamentClient->getTournaments($filters);

                return [
                    'status' => $response['status'] ?? 500,
                    'body' => $response['data'] ?? null,
                ];
            });

            return response()->json($cached['body'], $cached['status']);
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve tournaments');
        }
    }

    /**
     * Get full aggregated tournament details
     */
    public function show(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $cacheKey = "tournament_details_public:{$id}";
            $cacheTtl = 600; // 10 minutes

            $data = Cache::remember($cacheKey, $cacheTtl, function () use ($id) {
                $response = $this->tournamentAggregator->getTournamentDetails($id);
                
                if (!$response['success']) {
                    return null;
                }

                return $response['data'];
            });
            // Log::info("Tournament details: " . json_encode($data));

            if (!$data) {
                return ApiResponse::notFound('Tournament not found');
            }

            return ApiResponse::success($data, 'Tournament details retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve tournament details');
        }
    }

    /**
     * Get tournament overview
     */
    public function overview(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $cacheKey = "tournament_overview_public:{$id}";
            $cacheTtl = 300; // 5 minutes

            $data = Cache::remember($cacheKey, $cacheTtl, function () use ($id) {
                $response = $this->tournamentAggregator->getTournamentOverview($id);
                
                if (!$response['success']) {
                    return null;
                }

                return $response['data'];
            });

            if (!$data) {
                return ApiResponse::notFound('Tournament not found');
            }

            return ApiResponse::success($data, 'Tournament overview retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve tournament overview');
        }
    }

    /**
     * Get tournament teams
     */
    public function teams(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $cacheKey = "tournament_teams_public:{$id}";
            $cacheTtl = 600; // 10 minutes

            $data = Cache::remember($cacheKey, $cacheTtl, function () use ($id) {
                $response = $this->tournamentClient->getTournamentTeams($id);
                
                if (!$response['success']) {
                    return [];
                }

                return $response['data'];
            });

            return ApiResponse::success($data, 'Tournament teams retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve tournament teams');
        }
    }

    /**
     * Get tournament matches
     */
    public function matches(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'nullable|string|in:upcoming,live,completed',
                'page' => 'nullable|integer|min:1',
                'limit' => 'nullable|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $filters = array_merge($request->only(['status', 'page', 'limit']), [
                'tournament_id' => $id
            ]);
            
            $cacheKey = "tournament_matches_public:{$id}:" . md5(serialize($filters));
            $cacheTtl = 180; // 3 minutes

            $cached = Cache::remember($cacheKey, $cacheTtl, function () use ($filters) {
                $response = $this->tournamentClient->getTournamentMatches($filters['tournament_id'], $filters);

                Log::info("Fetched tournament matches for tournament {$filters['tournament_id']}", [
                    'response' => $response
                ]);
                return [
                    'status' => $response['status'] ?? 500,
                    'body' => $response['data'] ?? null,
                ];
            });

            return response()->json($cached['body'], $cached['status']);
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve tournament matches');
        }
    }
}
