<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\Public\PublicApiController;
use App\Services\PublicCacheService;
use App\Services\TournamentServiceClient;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Public Search Controller for Team Service
 *
 * Handles search functionality for teams.
 */
class PublicSearchController extends PublicApiController
{
    protected PublicCacheService $cacheService;
    protected TournamentServiceClient $tournamentServiceClient;
    protected int $defaultCacheTtl = 600; // 10 minutes
    protected int $maxResults = 10;

    public function __construct(
        PublicCacheService $cacheService,
        TournamentServiceClient $tournamentServiceClient
    ) {
        parent::__construct();
        $this->cacheService = $cacheService;
        $this->tournamentServiceClient = $tournamentServiceClient;
    }

    /**
     * Search teams
     *
     * GET /api/public/search/teams?q={query}
     */
    public function searchTeams(Request $request): JsonResponse
    {
        try {
            $query = $request->query('q', '');
            $query = trim($query);

            if (empty($query)) {
                return $this->errorResponse('Search query is required', 400, null, 'SEARCH_QUERY_REQUIRED');
            }

            $sanitizedQuery = $this->sanitizeQuery($query);

            $cacheKey = $this->cacheService->generateKey("search:teams", ['q' => $query]);
            $tags = ["public:search:teams", "public:search:teams:" . md5($query)];
            $ttl = 600; // 10 minutes

            $data = $this->cacheService->remember($cacheKey, $ttl, function () use ($sanitizedQuery, $query) {
                return $this->performTeamSearch($sanitizedQuery, $query);
            }, $tags, 'static');

            return $this->successResponse($data, 'Teams found', 200, $ttl);
        } catch (Throwable $e) {
            Log::error('Failed to search teams', [
                'query' => $request->query('q'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Failed to search teams', 500);
        }
    }

    /**
     * Perform team search
     */
    protected function performTeamSearch(string $sanitizedQuery, string $originalQuery): array
    {
        $likeQuery = '%' . addcslashes($sanitizedQuery, '%_\\') . '%';

        // Search teams by name
        $teams = Team::where('name', 'LIKE', $likeQuery)
            ->limit($this->maxResults * 2)
            ->get();

        // Enrich with tournament info and calculate relevance
        $results = [];
        foreach ($teams as $team) {
            $tournament = $this->tournamentServiceClient->getPublicTournament($team->tournament_id);
            $relevance = $this->calculateTeamRelevance($team->name, $tournament['name'] ?? '', $originalQuery);

            $results[] = [
                'id' => $team->id,
                'name' => $team->name,
                'logo' => $team->logo,
                'tournament' => $tournament ? [
                    'id' => $tournament['id'] ?? null,
                    'name' => $tournament['name'] ?? null,
                ] : null,
                'relevance' => $relevance,
            ];
        }

        // Sort by relevance
        usort($results, function ($a, $b) {
            return $b['relevance'] <=> $a['relevance'];
        });

        return [
            'query' => $originalQuery,
            'teams' => array_slice($results, 0, $this->maxResults),
            'total' => count($results),
            'returned' => min(count($results), $this->maxResults),
        ];
    }

    /**
     * Calculate relevance score for team
     */
    protected function calculateTeamRelevance(string $teamName, string $tournamentName, string $query): float
    {
        $queryLower = mb_strtolower($query);
        $teamNameLower = mb_strtolower($teamName);
        $tournamentNameLower = mb_strtolower($tournamentName);

        $relevance = 0;

        // Exact match in team name
        if ($teamNameLower === $queryLower) {
            $relevance += 100;
        } elseif (strpos($teamNameLower, $queryLower) !== false) {
            $relevance += 50;
        }

        // Word matches in team name
        $queryWords = array_filter(explode(' ', $queryLower));
        foreach ($queryWords as $word) {
            if (strpos($teamNameLower, $word) !== false) {
                $relevance += 10;
            }
        }

        // Tournament name match
        if (strpos($tournamentNameLower, $queryLower) !== false) {
            $relevance += 20;
        }

        return round($relevance, 2);
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
