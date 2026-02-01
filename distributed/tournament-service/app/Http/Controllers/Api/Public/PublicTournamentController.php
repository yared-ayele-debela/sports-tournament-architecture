<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\Public\PublicApiController;
use App\Services\PublicCacheService;
use App\Services\Clients\TeamServiceClient;
use App\Services\Clients\MatchServiceClient;
use App\Models\Tournament;
use App\Models\Sport;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Public Tournament Controller
 *
 * Handles public-facing tournament endpoints with caching and optimization.
 */
class PublicTournamentController extends PublicApiController
{
    protected PublicCacheService $cacheService;
    protected TeamServiceClient $teamServiceClient;
    protected MatchServiceClient $matchServiceClient;

    protected int $defaultCacheTtl = 300; // 5 minutes
    protected array $defaultCacheTags = ['public-api', 'tournaments'];

    public function __construct(
        PublicCacheService $cacheService,
        TeamServiceClient $teamServiceClient,
        MatchServiceClient $matchServiceClient
    ) {
        $this->cacheService = $cacheService;
        $this->teamServiceClient = $teamServiceClient;
        $this->matchServiceClient = $matchServiceClient;
    }

    /**
     * List all public tournaments
     *
     * GET /api/public/tournaments
     * Query params: ?status=ongoing&sport_id=1&limit=20
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validate query parameters
            $validated = $request->validate([
                'status' => 'nullable|string|in:ongoing,completed',
                'sport_id' => 'nullable|integer|exists:sports,id',
                'limit' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
            ]);

            // Generate cache key from route and query params
            $cacheKey = $this->cacheService->generateKeyFromRoute(
                'public.tournaments.index',
                $validated
            );

            // Cache with tags
            $data = $this->cacheService->remember(
                $cacheKey,
                300, // 5 minutes
                function () use ($validated) {
                    return $this->fetchTournaments($validated);
                },
                ['public-api', 'tournaments', 'tournaments:list', 'public:tournaments:list'],
                'live'
            );

            return $this->successResponse($data, 'Tournaments retrieved successfully', 200, 300);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(
                'Invalid query parameters',
                422,
                $e->errors(),
                'VALIDATION_ERROR'
            );
        } catch (Throwable $e) {
            Log::error('Failed to retrieve tournaments', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->handleServiceFailure($e, 'Failed to retrieve tournaments', 'TournamentService');
        }
    }

    /**
     * Get featured tournaments
     *
     * GET /api/public/tournaments/featured
     */
    public function featured(Request $request): JsonResponse
    {
        try {
            $cacheKey = $this->cacheService->generateKey('tournaments:featured');

            $data = $this->cacheService->remember(
                $cacheKey,
                600, // 10 minutes
                function () {
                    return $this->fetchFeaturedTournaments();
                },
                ['public-api', 'tournaments', 'tournaments:featured', 'public:tournaments:featured'],
                'live'
            );

            return $this->successResponse($data, 'Featured tournaments retrieved successfully', 200, 600);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve featured tournaments', [
                'error' => $e->getMessage(),
            ]);

            return $this->handleServiceFailure($e, 'Failed to retrieve featured tournaments', 'TournamentService');
        }
    }

    /**
     * Get upcoming tournaments
     *
     * GET /api/public/tournaments/upcoming
     */
    public function upcoming(Request $request): JsonResponse
    {
        try {
            $cacheKey = $this->cacheService->generateKey('tournaments:upcoming');

            $data = $this->cacheService->remember(
                $cacheKey,
                900, // 15 minutes
                function () {
                    return $this->fetchUpcomingTournaments();
                },
                ['public-api', 'tournaments', 'tournaments:upcoming', 'public:tournaments:upcoming'],
                'live'
            );

            return $this->successResponse($data, 'Upcoming tournaments retrieved successfully', 200, 900);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve upcoming tournaments', [
                'error' => $e->getMessage(),
            ]);

            return $this->handleServiceFailure($e, 'Failed to retrieve upcoming tournaments', 'TournamentService');
        }
    }

    /**
     * Get tournament details
     *
     * GET /api/public/tournaments/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $cacheKey = $this->cacheService->generateKey('tournament', ['id' => $id]);

            $data = $this->cacheService->remember(
                $cacheKey,
                300, // 5 minutes
                function () use ($id) {
                    return $this->fetchTournamentDetails($id);
                },
                ['public-api', 'tournaments', "tournament:{$id}", "public:tournament:{$id}"],
                'live'
            );

            if (!$data) {
                return $this->errorResponse('Tournament not found', 404, null, 'TOURNAMENT_NOT_FOUND');
            }

            return $this->successResponse($data, 'Tournament retrieved successfully', 200, 300);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve tournament', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->handleServiceFailure($e, 'Failed to retrieve tournament', 'TournamentService');
        }
    }

    /**
     * List all sports with tournament counts
     *
     * GET /api/public/sports
     */
    public function sports(Request $request): JsonResponse
    {
        try {
            $cacheKey = $this->cacheService->generateKey('sports:list');

            $data = $this->cacheService->remember(
                $cacheKey,
                3600, // 1 hour
                function () {
                    return $this->fetchSports();
                },
                ['public-api', 'sports', 'sports:list'],
                'static'
            );

            return $this->successResponse($data, 'Sports retrieved successfully', 200, 3600);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve sports', [
                'error' => $e->getMessage(),
            ]);

            return $this->handleServiceFailure($e, 'Failed to retrieve sports', 'TournamentService');
        }
    }

    /**
     * List all venues
     *
     * GET /api/public/venues
     */
    public function venues(Request $request): JsonResponse
    {
        try {
            $cacheKey = $this->cacheService->generateKey('venues:list');

            $data = $this->cacheService->remember(
                $cacheKey,
                3600, // 1 hour
                function () {
                    return $this->fetchVenues();
                },
                ['public-api', 'venues', 'venues:list'],
                'static'
            );

            return $this->successResponse($data, 'Venues retrieved successfully', 200, 3600);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve venues', [
                'error' => $e->getMessage(),
            ]);

            return $this->handleServiceFailure($e, 'Failed to retrieve venues', 'TournamentService');
        }
    }

    /**
     * Get venue details
     *
     * GET /api/public/venues/{id}
     */
    public function showVenue(Venue $venue): JsonResponse
    {
        try {
            $cacheKey = $this->cacheService->generateKey("venue:{$venue->id}");

            $data = $this->cacheService->remember(
                $cacheKey,
                3600, // 1 hour
                function () use ($venue) {
                    return $this->fetchVenueDetails($venue);
                },
                ['public-api', 'venues', "venue:{$venue->id}"],
                'static'
            );

            return $this->successResponse($data, 'Venue details retrieved successfully', 200, 3600);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve venue details', [
                'venue_id' => $venue->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Venue not found', 404, null, 'VENUE_NOT_FOUND');
        }
    }

    /**
     * Fetch tournaments with filters
     */
    protected function fetchTournaments(array $filters): array
    {
        $query = Tournament::query()
            ->select([
                'id',
                'name',
                'sport_id',
                'location',
                'start_date',
                'end_date',
                'status',
            ])
            ->with([
                'sport:id,name',
                'settings:id,tournament_id,match_duration,win_rest_time,daily_start_time,daily_end_time',
            ])
            ->whereIn('status', ['ongoing', 'completed']) // Only public tournaments
            ->orderByDesc('start_date');

        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['sport_id'])) {
            $query->where('sport_id', $filters['sport_id']);
        }

        $limit = $filters['limit'] ?? 20;
        $page = $filters['page'] ?? 1;

        $tournaments = $query->paginate($limit, ['*'], 'page', $page);

        // Get team and match counts for each tournament
        $tournamentIds = $tournaments->pluck('id')->toArray();
        $teamCounts = $this->getTeamCounts($tournamentIds);
        $matchCounts = $this->getMatchCounts($tournamentIds);

        // Transform data
        $data = $tournaments->map(function ($tournament) use ($teamCounts, $matchCounts) {
            return [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'sport' => [
                    'id' => $tournament->sport->id,
                    'name' => $tournament->sport->name,
                ],
                'start_date' => $tournament->start_date->toISOString(),
                'end_date' => $tournament->end_date->toISOString(),
                'status' => $tournament->status,
                'venue_count' => 1, // Since location is a string, we count it as 1
                'team_count' => $teamCounts[$tournament->id] ?? 0,
                'match_count' => $matchCounts[$tournament->id] ?? 0,
                'settings' => $tournament->settings ? [
                    'match_duration' => $tournament->settings->match_duration,
                    'win_rest_time' => $tournament->settings->win_rest_time,
                    'daily_start_time' => $tournament->settings->daily_start_time?->format('H:i'),
                    'daily_end_time' => $tournament->settings->daily_end_time?->format('H:i'),
                ] : null,
            ];
        });

        return [
            'data' => $data->toArray(),
            'pagination' => [
                'current_page' => $tournaments->currentPage(),
                'last_page' => $tournaments->lastPage(),
                'per_page' => $tournaments->perPage(),
                'total' => $tournaments->total(),
                'from' => $tournaments->firstItem(),
                'to' => $tournaments->lastItem(),
            ],
        ];
    }

    /**
     * Fetch featured tournaments
     */
    protected function fetchFeaturedTournaments(): array
    {
        // Featured tournaments: Top 5 ongoing tournaments ordered by start_date
        $tournaments = Tournament::query()
            ->select([
                'id',
                'name',
                'sport_id',
                'location',
                'start_date',
                'end_date',
                'status',
            ])
            ->with([
                'sport:id,name',
                'settings:id,tournament_id,match_duration,win_rest_time,daily_start_time,daily_end_time',
            ])
            ->where('status', 'ongoing')
            ->orderByDesc('start_date')
            ->limit(5)
            ->get();

        $tournamentIds = $tournaments->pluck('id')->toArray();
        $teamCounts = $this->getTeamCounts($tournamentIds);
        $matchCounts = $this->getMatchCounts($tournamentIds);

        return $tournaments->map(function ($tournament) use ($teamCounts, $matchCounts) {
            return [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'sport' => [
                    'id' => $tournament->sport->id,
                    'name' => $tournament->sport->name,
                ],
                'start_date' => $tournament->start_date->toISOString(),
                'end_date' => $tournament->end_date->toISOString(),
                'status' => $tournament->status,
                'venue_count' => 1,
                'team_count' => $teamCounts[$tournament->id] ?? 0,
                'match_count' => $matchCounts[$tournament->id] ?? 0,
                'settings' => $tournament->settings ? [
                    'match_duration' => $tournament->settings->match_duration,
                    'win_rest_time' => $tournament->settings->win_rest_time,
                    'daily_start_time' => $tournament->settings->daily_start_time?->format('H:i'),
                    'daily_end_time' => $tournament->settings->daily_end_time?->format('H:i'),
                ] : null,
            ];
        })->toArray();
    }

    /**
     * Fetch upcoming tournaments
     */
    protected function fetchUpcomingTournaments(): array
    {
        $tournaments = Tournament::query()
            ->select([
                'id',
                'name',
                'sport_id',
                'location',
                'start_date',
                'end_date',
                'status',
            ])
            ->with([
                'sport:id,name',
                'settings:id,tournament_id,match_duration,win_rest_time,daily_start_time,daily_end_time',
            ])
            ->where('status', 'planned')
            ->where('start_date', '>', now())
            ->orderBy('start_date')
            ->limit(10)
            ->get();

        $tournamentIds = $tournaments->pluck('id')->toArray();
        $teamCounts = $this->getTeamCounts($tournamentIds);
        $matchCounts = $this->getMatchCounts($tournamentIds);

        return $tournaments->map(function ($tournament) use ($teamCounts, $matchCounts) {
            return [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'sport' => [
                    'id' => $tournament->sport->id,
                    'name' => $tournament->sport->name,
                ],
                'start_date' => $tournament->start_date->toISOString(),
                'end_date' => $tournament->end_date->toISOString(),
                'status' => $tournament->status,
                'venue_count' => 1,
                'team_count' => $teamCounts[$tournament->id] ?? 0,
                'match_count' => $matchCounts[$tournament->id] ?? 0,
                'settings' => $tournament->settings ? [
                    'match_duration' => $tournament->settings->match_duration,
                    'win_rest_time' => $tournament->settings->win_rest_time,
                    'daily_start_time' => $tournament->settings->daily_start_time?->format('H:i'),
                    'daily_end_time' => $tournament->settings->daily_end_time?->format('H:i'),
                ] : null,
            ];
        })->toArray();
    }

    /**
     * Fetch tournament details
     */
    protected function fetchTournamentDetails(int $id): ?array
    {
        $tournament = Tournament::query()
            ->select([
                'id',
                'name',
                'sport_id',
                'location',
                'start_date',
                'end_date',
                'status',
                'created_at',
                'updated_at',
            ])
            ->with([
                'sport:id,name,description',
                'settings:id,tournament_id,match_duration,win_rest_time,daily_start_time,daily_end_time',
            ])
            ->whereIn('status', ['ongoing', 'completed']) // Only public tournaments
            ->find($id);

        if (!$tournament) {
            return null;
        }

        // Get counts
        $teamCount = $this->getTeamCount($id);
        $matchCount = $this->getMatchCount($id);

        return [
            'id' => $tournament->id,
            'name' => $tournament->name,
            'sport' => [
                'id' => $tournament->sport->id,
                'name' => $tournament->sport->name,
                'description' => $tournament->sport->description,
            ],
            'location' => $tournament->location,
            'start_date' => $tournament->start_date->toISOString(),
            'end_date' => $tournament->end_date->toISOString(),
            'status' => $tournament->status,
            'venue_count' => 1,
            'team_count' => $teamCount,
            'match_count' => $matchCount,
            'settings' => $tournament->settings ? [
                'match_duration' => $tournament->settings->match_duration,
                'win_rest_time' => $tournament->settings->win_rest_time,
                'daily_start_time' => $tournament->settings->daily_start_time?->format('H:i'),
                'daily_end_time' => $tournament->settings->daily_end_time?->format('H:i'),
            ] : null,
            'created_at' => $tournament->created_at->toISOString(),
            'updated_at' => $tournament->updated_at->toISOString(),
        ];
    }

    /**
     * Fetch sports with tournament counts
     */
    protected function fetchSports(): array
    {
        $sports = Sport::query()
            ->select(['id', 'name', 'description'])
            ->withCount(['tournaments' => function ($query) {
                $query->whereIn('status', ['ongoing', 'completed']);
            }])
            ->orderBy('name')
            ->get();

        return $sports->map(function ($sport) {
            return [
                'id' => $sport->id,
                'name' => $sport->name,
                'description' => $sport->description,
                'tournament_count' => $sport->tournaments_count,
            ];
        })->toArray();
    }

    /**
     * Fetch venues
     */
    protected function fetchVenues(): array
    {
        $venues = Venue::query()
            ->select(['id', 'name', 'location', 'capacity'])
            ->orderBy('name')
            ->get();

        return $venues->map(function ($venue) {
            return [
                'id' => $venue->id,
                'name' => $venue->name,
                'location' => $venue->location,
                'capacity' => $venue->capacity,
            ];
        })->toArray();
    }

    /**
     * Fetch venue details
     */
    protected function fetchVenueDetails(Venue $venue): array
    {
        return [
            'id' => $venue->id,
            'name' => $venue->name,
            'location' => $venue->location,
            'address' => $venue->address ?? null,
            'capacity' => $venue->capacity,
            'description' => $venue->description ?? null,
        ];
    }

    /**
     * Get team counts for multiple tournaments
     */
    protected function getTeamCounts(array $tournamentIds): array
    {
        if (empty($tournamentIds)) {
            return [];
        }

        try {
            // Try to get counts from team service
            // Note: This endpoint needs to be implemented in team-service
            $counts = [];
            foreach ($tournamentIds as $tournamentId) {
                try {
                    $response = $this->teamServiceClient->getTournamentTeams($tournamentId);
                    $teams = $response['data'] ?? [];
                    $counts[$tournamentId] = is_array($teams) ? count($teams) : 0;
                } catch (Throwable $e) {
                    $counts[$tournamentId] = 0;
                }
            }
            return $counts;
        } catch (Throwable $e) {
            Log::warning('Failed to fetch team counts', [
                'tournament_ids' => $tournamentIds,
                'error' => $e->getMessage(),
            ]);
            return array_fill_keys($tournamentIds, 0);
        }
    }

    /**
     * Get team count for a single tournament
     */
    protected function getTeamCount(int $tournamentId): int
    {
        $counts = $this->getTeamCounts([$tournamentId]);
        return $counts[$tournamentId] ?? 0;
    }

    /**
     * Get match counts for multiple tournaments
     */
    protected function getMatchCounts(array $tournamentIds): array
    {
        if (empty($tournamentIds)) {
            return [];
        }

        try {
            // Try to get counts from match service
            // Note: This endpoint needs to be implemented in match-service
            $counts = [];
            foreach ($tournamentIds as $tournamentId) {
                try {
                    $response = $this->matchServiceClient->getTournamentMatches($tournamentId, []);
                    if (!$response['success']) {
                        $counts[$tournamentId] = 0;
                        continue;
                    }

                    // Response format: ['success' => true, 'data' => {...}, 'status' => 200]
                    // data might be: {data: [...], pagination: {...}} or just [...]
                    $data = $response['data'] ?? [];

                    // If data is paginated, get total from pagination
                    if (isset($data['pagination']['total'])) {
                        $counts[$tournamentId] = $data['pagination']['total'];
                    } elseif (isset($data['data']) && is_array($data['data'])) {
                        $counts[$tournamentId] = count($data['data']);
                    } elseif (is_array($data)) {
                        $counts[$tournamentId] = count($data);
                    } else {
                        $counts[$tournamentId] = 0;
                    }
                } catch (Throwable $e) {
                    $counts[$tournamentId] = 0;
                }
            }
            return $counts;
        } catch (Throwable $e) {
            Log::warning('Failed to fetch match counts', [
                'tournament_ids' => $tournamentIds,
                'error' => $e->getMessage(),
            ]);
            return array_fill_keys($tournamentIds, 0);
        }
    }

    /**
     * Get match count for a single tournament
     */
    protected function getMatchCount(int $tournamentId): int
    {
        $counts = $this->getMatchCounts([$tournamentId]);
        return $counts[$tournamentId] ?? 0;
    }
}
