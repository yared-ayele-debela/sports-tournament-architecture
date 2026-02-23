<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\Public\PublicApiController;
use App\Services\PublicCacheService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Public Meta Search Controller
 *
 * Aggregates search results from all services.
 */
class PublicMetaSearchController extends PublicApiController
{
    protected PublicCacheService $cacheService;
    protected int $defaultCacheTtl = 600; // 10 minutes

    // Service URLs
    protected string $teamServiceUrl;
    protected string $matchServiceUrl;

    public function __construct(PublicCacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
        $this->teamServiceUrl = env('TEAM_SERVICE_URL', 'http://localhost:8003');
        $this->matchServiceUrl = env('MATCH_SERVICE_URL', 'http://localhost:8004');
    }

    /**
     * Meta search across all services
     *
     * GET /api/public/search?q={query}
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $query = $request->query('q', '');
            $query = trim($query);

            if (empty($query)) {
                return $this->errorResponse('Search query is required', 400, null, 'SEARCH_QUERY_REQUIRED');
            }

            $sanitizedQuery = $this->sanitizeQuery($query);

            $cacheKey = $this->cacheService->generateKey("search:all", ['q' => $query]);
            $tags = ["public:search:all", "public:search:all:" . md5($query)];
            $ttl = 600; // 10 minutes

            $data = $this->cacheService->remember($cacheKey, $ttl, function () use ($sanitizedQuery, $query) {
                return $this->performMetaSearch($sanitizedQuery, $query);
            }, $tags, 'static');

            return $this->successResponse($data, 'Search results', 200, $ttl);
        } catch (Throwable $e) {
            Log::error('Failed to perform meta search', [
                'query' => $request->query('q'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Failed to perform search', 500);
        }
    }

    /**
     * Perform meta search across all services
     */
    protected function performMetaSearch(string $sanitizedQuery, string $originalQuery): array
    {
        $results = [
            'query' => $originalQuery,
            'tournaments' => [],
            'teams' => [],
            'matches' => [],
            'total' => 0,
        ];

        // Search tournaments (local)
        try {
            $searchController = app(\App\Http\Controllers\Api\Public\PublicSearchController::class);
            $tournamentRequest = Request::create("/api/public/search/tournaments", 'GET', ['q' => $originalQuery]);
            $tournamentResponse = $searchController->searchTournaments($tournamentRequest);
            $tournamentData = json_decode($tournamentResponse->getContent(), true);
            if (isset($tournamentData['data']['tournaments'])) {
                $results['tournaments'] = $tournamentData['data']['tournaments'];
            }
        } catch (Throwable $e) {
            Log::warning('Failed to search tournaments in meta search', [
                'query' => $originalQuery,
                'error' => $e->getMessage(),
            ]);
        }

        // Search teams (external service)
        try {
            /** @var \Illuminate\Http\Client\Response $teamResponse */
            $teamResponse = Http::timeout(5)->get("{$this->teamServiceUrl}/api/public/search/teams", [
                'q' => $originalQuery,
            ]);

            if ($teamResponse->successful()) {
                $teamData = $teamResponse->json();
                if (is_array($teamData) && isset($teamData['data']['teams'])) {
                    $results['teams'] = $teamData['data']['teams'];
                }
            }
        } catch (Throwable $e) {
            Log::warning('Failed to search teams in meta search', [
                'query' => $originalQuery,
                'error' => $e->getMessage(),
            ]);
        }

        // Search matches (external service)
        try {
            /** @var \Illuminate\Http\Client\Response $matchResponse */
            $matchResponse = Http::timeout(5)->get("{$this->matchServiceUrl}/api/public/search/matches", [
                'q' => $originalQuery,
            ]);

            if ($matchResponse->successful()) {
                $matchData = $matchResponse->json();
                if (is_array($matchData) && isset($matchData['data']['matches'])) {
                    $results['matches'] = $matchData['data']['matches'];
                }
            }
        } catch (Throwable $e) {
            Log::warning('Failed to search matches in meta search', [
                'query' => $originalQuery,
                'error' => $e->getMessage(),
            ]);
        }

        // Calculate totals
        $results['total'] = count($results['tournaments']) + count($results['teams']) + count($results['matches']);

        // Sort each category by relevance
        usort($results['tournaments'], function ($a, $b) {
            return ($b['relevance'] ?? 0) <=> ($a['relevance'] ?? 0);
        });
        usort($results['teams'], function ($a, $b) {
            return ($b['relevance'] ?? 0) <=> ($a['relevance'] ?? 0);
        });
        usort($results['matches'], function ($a, $b) {
            return ($b['relevance'] ?? 0) <=> ($a['relevance'] ?? 0);
        });

        return $results;
    }

    /**
     * Sanitize search query
     */
    protected function sanitizeQuery(string $query): string
    {
        $query = strip_tags($query);
        $query = trim($query);
        $query = mb_substr($query, 0, 100);
        return $query;
    }
}
