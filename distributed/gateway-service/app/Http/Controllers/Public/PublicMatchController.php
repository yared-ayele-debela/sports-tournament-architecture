<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Aggregators\MatchAggregator;
use App\Services\Clients\MatchServiceClient;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PublicMatchController extends Controller
{
    protected MatchAggregator $matchAggregator;
    protected MatchServiceClient $matchClient;

    public function __construct(
        MatchAggregator $matchAggregator,
        MatchServiceClient $matchClient
    ) {
        $this->matchAggregator = $matchAggregator;
        $this->matchClient = $matchClient;
    }

    /**
     * List matches with optional filters
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'tournament_id' => 'nullable|integer|min:1',
                'team_id' => 'nullable|integer|min:1',
                'status' => 'nullable|string|in:upcoming,live,completed',
                'date' => 'nullable|date',
                'page' => 'nullable|integer|min:1',
                'limit' => 'nullable|integer|min:1|max:100',
                'sort' => 'nullable|string|in:date,start_time',
                'order' => 'nullable|string|in:asc,desc',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $filters = $request->only([
                'tournament_id', 'team_id', 'status', 'date', 
                'page', 'limit', 'sort', 'order'
            ]);
            
            $cacheKey = 'matches_list_public:' . md5(serialize($filters));
            $cacheTtl = 180; // 3 minutes

            $cached = Cache::remember($cacheKey, $cacheTtl, function () use ($filters) {
                $response = $this->matchClient->getMatches($filters);

                return [
                    'status' => $response['status'] ?? 500,
                    'body' => $response['data'] ?? null,
                ];
            });

            return response()->json($cached['body'], $cached['status']);
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve matches');
        }
    }

    /**
     * Get match details with events
     */
    public function show(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $cacheKey = "match_details_public:{$id}";
            // Cache TTL will be determined by match status in the aggregator
            $data = Cache::remember($cacheKey, 60, function () use ($id) {
                $response = $this->matchAggregator->getMatchDetails($id);
                
                if (!$response['success']) {
                    return null;
                }

                return $response['data'];
            });

            if (!$data) {
                return ApiResponse::notFound('Match not found');
            }

            return ApiResponse::success($data, 'Match details retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve match details');
        }
    }

    /**
     * Get match events only
     */
    public function events(Request $request, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $cacheKey = "match_events_public:{$id}";
            $cacheTtl = 300; // 5 minutes

            $cached = Cache::remember($cacheKey, $cacheTtl, function () use ($id) {
                $response = $this->matchClient->getMatchEvents($id);

                return [
                    'status' => $response['status'] ?? 500,
                    'body' => $response['data'] ?? null,
                ];
            });

            return response()->json($cached['body'], $cached['status']);
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve match events');
        }
    }

    /**
     * Get live matches (no caching for real-time data)
     */
    public function live(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $response = $this->matchClient->getLiveMatches();

            return response()->json($response['data'] ?? null, $response['status'] ?? 500);
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve live matches');
        }
    }

    /**
     * Get matches by date
     */
    public function byDate(Request $request, string $date): \Illuminate\Http\JsonResponse
    {
        try {
            // Validate date format
            if (!\DateTime::createFromFormat('Y-m-d', $date)) {
                return ApiResponse::validationError(['date' => 'Invalid date format. Use Y-m-d format.']);
            }

            $filters = $request->only(['tournament_id', 'team_id']);
            $cacheKey = "matches_by_date_public:{$date}:" . md5(serialize($filters));
            $cacheTtl = 600; // 10 minutes

            $cached = Cache::remember($cacheKey, $cacheTtl, function () use ($date, $filters) {
                $response = $this->matchClient->getMatchesByDate($date, $filters);

                return [
                    'status' => $response['status'] ?? 500,
                    'body' => $response['data'] ?? null,
                ];
            });

            return response()->json($cached['body'], $cached['status']);
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve matches by date');
        }
    }

    /**
     * Get upcoming matches
     */
    public function upcoming(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'limit' => 'nullable|integer|min:1|max:50',
                'tournament_id' => 'nullable|integer|min:1',
                'team_id' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $filters = $request->only(['limit', 'tournament_id', 'team_id']);
            $cacheKey = 'upcoming_matches_public:' . md5(serialize($filters));
            $cacheTtl = 120; // 2 minutes

            $cached = Cache::remember($cacheKey, $cacheTtl, function () use ($filters) {
                $response = $this->matchClient->getUpcomingMatches($filters);

                return [
                    'status' => $response['status'] ?? 500,
                    'body' => $response['data'] ?? null,
                ];
            });

            return response()->json($cached['body'], $cached['status']);
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve upcoming matches');
        }
    }

    /**
     * Get completed matches
     */
    public function completed(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'limit' => 'nullable|integer|min:1|max:50',
                'tournament_id' => 'nullable|integer|min:1',
                'team_id' => 'nullable|integer|min:1',
                'page' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $filters = $request->only(['limit', 'tournament_id', 'team_id', 'page']);
            $cacheKey = 'completed_matches_public:' . md5(serialize($filters));
            $cacheTtl = 300; // 5 minutes

            $cached = Cache::remember($cacheKey, $cacheTtl, function () use ($filters) {
                $response = $this->matchClient->getCompletedMatches($filters);

                return [
                    'status' => $response['status'] ?? 500,
                    'body' => $response['data'] ?? null,
                ];
            });

            return response()->json($cached['body'], $cached['status']);
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve completed matches');
        }
    }
}
