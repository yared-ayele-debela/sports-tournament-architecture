<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\Public\PublicApiController;
use App\Services\PublicCacheService;
use App\Services\Clients\TeamServiceClient;
use App\Services\Clients\TournamentServiceClient;
use App\Models\MatchGame;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Throwable;

/**
 * Public Search Controller for Match Service
 *
 * Handles search functionality for matches.
 */
class PublicSearchController extends PublicApiController
{
    protected PublicCacheService $cacheService;
    protected TeamServiceClient $teamServiceClient;
    protected TournamentServiceClient $tournamentServiceClient;
    protected int $defaultCacheTtl = 300; // 5 minutes
    protected int $maxResults = 10;

    public function __construct(
        PublicCacheService $cacheService,
        TeamServiceClient $teamServiceClient,
        TournamentServiceClient $tournamentServiceClient
    ) {
        parent::__construct();
        $this->cacheService = $cacheService;
        $this->teamServiceClient = $teamServiceClient;
        $this->tournamentServiceClient = $tournamentServiceClient;
    }

    /**
     * Search matches
     *
     * GET /api/public/search/matches?q={query}
     */
    public function searchMatches(Request $request): JsonResponse
    {
        try {
            $query = $request->query('q', '');
            $query = trim($query);

            if (empty($query)) {
                return $this->errorResponse('Search query is required', 400, null, 'SEARCH_QUERY_REQUIRED');
            }

            $sanitizedQuery = $this->sanitizeQuery($query);

            $cacheKey = $this->cacheService->generateKey("search:matches", ['q' => $query]);
            $tags = ["public:search:matches", "public:search:matches:" . md5($query)];
            $ttl = 300; // 5 minutes

            $data = $this->cacheService->remember($cacheKey, $ttl, function () use ($sanitizedQuery, $query) {
                return $this->performMatchSearch($sanitizedQuery, $query);
            }, $tags, 'live');

            return $this->successResponse($data, 'Matches found', 200, $ttl);
        } catch (Throwable $e) {
            Log::error('Failed to search matches', [
                'query' => $request->query('q'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Failed to search matches', 500);
        }
    }

    /**
     * Perform match search
     */
    protected function performMatchSearch(string $sanitizedQuery, string $originalQuery): array
    {
        $likeQuery = '%' . addcslashes($sanitizedQuery, '%_\\') . '%';

        // Get all matches (we'll filter by team/tournament names)
        $matches = MatchGame::limit($this->maxResults * 3)
            ->orderBy('match_date', 'desc')
            ->get();

        // Enrich with team and tournament info, then filter
        $results = [];
        foreach ($matches as $match) {
            $homeTeam = $this->teamServiceClient->getPublicTeam($match->home_team_id);
            $awayTeam = $this->teamServiceClient->getPublicTeam($match->away_team_id);
            $tournament = $this->tournamentServiceClient->getPublicTournament($match->tournament_id);

            $homeTeamName = $homeTeam['name'] ?? '';
            $awayTeamName = $awayTeam['name'] ?? '';
            $tournamentName = $tournament['name'] ?? '';

            // Check if query matches any of these
            $matchesQuery = $this->matchesQuery($originalQuery, $homeTeamName, $awayTeamName, $tournamentName, $match->match_date);

            if ($matchesQuery) {
                $relevance = $this->calculateMatchRelevance(
                    $originalQuery,
                    $homeTeamName,
                    $awayTeamName,
                    $tournamentName,
                    $match->match_date
                );

                $results[] = [
                    'id' => $match->id,
                    'tournament' => $tournament ? [
                        'id' => $tournament['id'] ?? null,
                        'name' => $tournament['name'] ?? null,
                    ] : null,
                    'home_team' => $homeTeam ? [
                        'id' => $homeTeam['id'] ?? null,
                        'name' => $homeTeam['name'] ?? null,
                    ] : null,
                    'away_team' => $awayTeam ? [
                        'id' => $awayTeam['id'] ?? null,
                        'name' => $awayTeam['name'] ?? null,
                    ] : null,
                    'match_date' => $match->match_date?->toISOString(),
                    'status' => $match->status,
                    'home_score' => $match->home_score,
                    'away_score' => $match->away_score,
                    'relevance' => $relevance,
                ];
            }
        }

        // Sort by relevance
        usort($results, function ($a, $b) {
            return $b['relevance'] <=> $a['relevance'];
        });

        return [
            'query' => $originalQuery,
            'matches' => array_slice($results, 0, $this->maxResults),
            'total' => count($results),
            'returned' => min(count($results), $this->maxResults),
        ];
    }

    /**
     * Check if match matches query
     */
    protected function matchesQuery(string $query, string $homeTeam, string $awayTeam, string $tournament, $matchDate): bool
    {
        $queryLower = mb_strtolower($query);
        $homeTeamLower = mb_strtolower($homeTeam);
        $awayTeamLower = mb_strtolower($awayTeam);
        $tournamentLower = mb_strtolower($tournament);

        // Check team names
        if (strpos($homeTeamLower, $queryLower) !== false || strpos($awayTeamLower, $queryLower) !== false) {
            return true;
        }

        // Check tournament name
        if (strpos($tournamentLower, $queryLower) !== false) {
            return true;
        }

        // Check date (if query looks like a date)
        if ($matchDate && $this->isDateQuery($query)) {
            try {
                $queryDate = Carbon::parse($query);
                $matchDateCarbon = Carbon::parse($matchDate);
                if ($queryDate->format('Y-m-d') === $matchDateCarbon->format('Y-m-d')) {
                    return true;
                }
            } catch (\Exception $e) {
                // Not a valid date
            }
        }

        return false;
    }

    /**
     * Calculate relevance score for match
     */
    protected function calculateMatchRelevance(string $query, string $homeTeam, string $awayTeam, string $tournament, $matchDate): float
    {
        $queryLower = mb_strtolower($query);
        $homeTeamLower = mb_strtolower($homeTeam);
        $awayTeamLower = mb_strtolower($awayTeam);
        $tournamentLower = mb_strtolower($tournament);

        $relevance = 0;

        // Team name matches
        if ($homeTeamLower === $queryLower || $awayTeamLower === $queryLower) {
            $relevance += 100;
        } elseif (strpos($homeTeamLower, $queryLower) !== false || strpos($awayTeamLower, $queryLower) !== false) {
            $relevance += 50;
        }

        // Tournament name match
        if ($tournamentLower === $queryLower) {
            $relevance += 80;
        } elseif (strpos($tournamentLower, $queryLower) !== false) {
            $relevance += 30;
        }

        // Date match
        if ($matchDate && $this->isDateQuery($query)) {
            try {
                $queryDate = Carbon::parse($query);
                $matchDateCarbon = Carbon::parse($matchDate);
                if ($queryDate->format('Y-m-d') === $matchDateCarbon->format('Y-m-d')) {
                    $relevance += 40;
                }
            } catch (\Exception $e) {
                // Not a valid date
            }
        }

        return round($relevance, 2);
    }

    /**
     * Check if query looks like a date
     */
    protected function isDateQuery(string $query): bool
    {
        // Simple heuristic: contains numbers and common date separators
        return preg_match('/\d{1,4}[-\/]\d{1,2}[-\/]\d{1,4}/', $query) > 0;
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
