<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\Public\PublicApiController;
use App\Services\PublicCacheService;
use App\Models\Tournament;
use App\Models\Sport;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Public Search Controller for Tournament Service
 *
 * Handles search functionality for tournaments.
 */
class PublicSearchController extends PublicApiController
{
    protected PublicCacheService $cacheService;
    protected int $defaultCacheTtl = 600; // 10 minutes
    protected int $maxResults = 10;

    public function __construct(PublicCacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * Search tournaments
     *
     * GET /api/public/search/tournaments?q={query}
     */
    public function searchTournaments(Request $request): JsonResponse
    {
        try {
            $query = $request->query('q', '');
            $query = trim($query);

            if (empty($query)) {
                return $this->errorResponse('Search query is required', 400, null, 'SEARCH_QUERY_REQUIRED');
            }

            // Sanitize query for SQL
            $sanitizedQuery = $this->sanitizeQuery($query);

            $cacheKey = $this->cacheService->generateKey("search:tournaments", ['q' => $query]);
            $tags = ["public:search:tournaments", "public:search:tournaments:" . md5($query)];
            $ttl = 600; // 10 minutes

            $data = $this->cacheService->remember($cacheKey, $ttl, function () use ($sanitizedQuery, $query) {
                return $this->performTournamentSearch($sanitizedQuery, $query);
            }, $tags, 'static');

            return $this->successResponse($data, 'Tournaments found', 200, $ttl);
        } catch (Throwable $e) {
            Log::error('Failed to search tournaments', [
                'query' => $request->query('q'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Failed to search tournaments', 500);
        }
    }

    /**
     * Perform tournament search
     */
    protected function performTournamentSearch(string $sanitizedQuery, string $originalQuery): array
    {
        // Escape special characters for LIKE
        $likeQuery = '%' . addcslashes($sanitizedQuery, '%_\\') . '%';

        // Try FULLTEXT search first (if available)
        $fulltextResults = $this->searchWithFulltext($sanitizedQuery);

        // Fallback to LIKE search
        if (empty($fulltextResults)) {
            $fulltextResults = $this->searchWithLike($likeQuery);
        }

        // Calculate relevance scores
        $results = $this->calculateRelevance($fulltextResults, $originalQuery);

        // Sort by relevance and limit
        usort($results, function ($a, $b) {
            return $b['relevance'] <=> $a['relevance'];
        });

        return [
            'query' => $originalQuery,
            'tournaments' => array_slice($results, 0, $this->maxResults),
            'total' => count($results),
            'returned' => min(count($results), $this->maxResults),
        ];
    }

    /**
     * Search using FULLTEXT (if available)
     */
    protected function searchWithFulltext(string $query): array
    {
        try {
            // Check if FULLTEXT index exists
            $indexes = DB::select("SHOW INDEX FROM tournaments WHERE Key_name = 'tournaments_name_fulltext'");

            if (empty($indexes)) {
                return [];
            }

            return Tournament::select('tournaments.*')
                ->selectRaw('MATCH(tournaments.name) AGAINST(? IN BOOLEAN MODE) as relevance_score', [$query])
                ->whereRaw('MATCH(tournaments.name) AGAINST(? IN BOOLEAN MODE)', [$query])
                ->with('sport')
                ->limit($this->maxResults * 2) // Get more for relevance calculation
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            Log::debug('FULLTEXT search not available, falling back to LIKE', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Search using LIKE
     */
    protected function searchWithLike(string $likeQuery): array
    {
        return Tournament::where('name', 'LIKE', $likeQuery)
            ->orWhereHas('sport', function ($q) use ($likeQuery) {
                $q->where('name', 'LIKE', $likeQuery);
            })
            ->with('sport')
            ->limit($this->maxResults * 2)
            ->get()
            ->toArray();
    }

    /**
     * Calculate relevance scores
     */
    protected function calculateRelevance(array $results, string $query): array
    {
        $queryLower = mb_strtolower($query);
        $queryWords = array_filter(explode(' ', $queryLower));

        return array_map(function ($tournament) use ($queryLower, $queryWords) {
            $name = mb_strtolower($tournament['name'] ?? '');
            $sportName = mb_strtolower($tournament['sport']['name'] ?? '');

            $relevance = 0;

            // Exact match in name (highest score)
            if ($name === $queryLower) {
                $relevance += 100;
            } elseif (strpos($name, $queryLower) !== false) {
                $relevance += 50;
            }

            // Word matches in name
            foreach ($queryWords as $word) {
                if (strpos($name, $word) !== false) {
                    $relevance += 10;
                }
            }

            // Sport name match
            if (strpos($sportName, $queryLower) !== false) {
                $relevance += 20;
            }

            // Use FULLTEXT relevance if available
            if (isset($tournament['relevance_score'])) {
                $relevance += (float) $tournament['relevance_score'] * 10;
            }

            return [
                'id' => $tournament['id'],
                'name' => $tournament['name'],
                'sport' => [
                    'id' => $tournament['sport']['id'] ?? null,
                    'name' => $tournament['sport']['name'] ?? null,
                ],
                'location' => $tournament['location'] ?? null,
                'start_date' => $tournament['start_date'] ?? null,
                'end_date' => $tournament['end_date'] ?? null,
                'status' => $tournament['status'] ?? null,
                'relevance' => round($relevance, 2),
            ];
        }, $results);
    }

    /**
     * Sanitize search query
     */
    protected function sanitizeQuery(string $query): string
    {
        // Remove dangerous characters but keep search terms
        $query = strip_tags($query);
        $query = trim($query);

        // Limit length
        $query = mb_substr($query, 0, 100);

        return $query;
    }
}
