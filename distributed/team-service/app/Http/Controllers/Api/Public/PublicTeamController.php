<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\Public\PublicApiController;
use App\Models\Team;
use App\Models\Player;
use App\Services\PublicCacheService;
use App\Services\TournamentServiceClient;
use App\Services\MatchServiceClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Public Team Controller
 *
 * Handles public-facing team endpoints with caching and optimization.
 */
class PublicTeamController extends PublicApiController
{
    protected PublicCacheService $cacheService;
    protected TournamentServiceClient $tournamentServiceClient;
    protected MatchServiceClient $matchServiceClient;

    protected int $defaultCacheTtl = 300; // 5 minutes
    protected array $defaultCacheTags = ['public-api', 'teams'];

    public function __construct(
        PublicCacheService $cacheService,
        TournamentServiceClient $tournamentServiceClient,
        MatchServiceClient $matchServiceClient
    ) {
        parent::__construct();
        $this->cacheService = $cacheService;
        $this->tournamentServiceClient = $tournamentServiceClient;
        $this->matchServiceClient = $matchServiceClient;
    }

    /**
     * List all teams in a tournament
     *
     * GET /api/public/tournaments/{tournamentId}/teams
     */
    public function tournamentTeams(Request $request, int $tournamentId): JsonResponse
    {
        try {
            // Validate tournament exists (with cache)
            $tournament = $this->tournamentServiceClient->getPublicTournament($tournamentId);

            if (!$tournament) {
                return $this->errorResponse('Tournament not found', 404, null, 'TOURNAMENT_NOT_FOUND');
            }

            // Validate query parameters
            $validated = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $page = $validated['page'] ?? 1;
            $perPage = $validated['per_page'] ?? 20;

            // Generate cache key
            $cacheKey = $this->cacheService->generateKey(
                "tournament:{$tournamentId}:teams",
                ['page' => $page, 'per_page' => $perPage]
            );

            // Cache with tags
            $data = $this->cacheService->remember(
                $cacheKey,
                600, // 10 minutes
                function () use ($tournamentId, $page, $perPage) {
                    return $this->fetchTournamentTeams($tournamentId, $page, $perPage);
                },
                [
                    'public-api',
                    'teams',
                    "tournament:{$tournamentId}",
                    "public:tournament:{$tournamentId}:teams",
                ],
                'live'
            );

            return $this->successResponse($data, 'Tournament teams retrieved successfully', 200, 600);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Invalid query parameters', 422, $e->errors(), 'VALIDATION_ERROR');
        } catch (Throwable $e) {
            Log::error('Failed to retrieve tournament teams', [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->handleServiceFailure($e, 'Failed to retrieve tournament teams', 'TeamService');
        }
    }

    /**
     * Get full team details
     *
     * GET /api/public/teams/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $cacheKey = $this->cacheService->generateKey('team', ['id' => $id]);

            $data = $this->cacheService->remember(
                $cacheKey,
                300, // 5 minutes
                function () use ($id) {
                    return $this->fetchTeamDetails($id);
                },
                [
                    'public-api',
                    'teams',
                    "team:{$id}",
                    "public:team:{$id}",
                ],
                'live'
            );

            if (!$data) {
                return $this->errorResponse('Team not found', 404, null, 'TEAM_NOT_FOUND');
            }

            return $this->successResponse($data, 'Team retrieved successfully', 200, 300);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve team', [
                'team_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->handleServiceFailure($e, 'Failed to retrieve team', 'TeamService');
        }
    }

    /**
     * List all players in a team
     *
     * GET /api/public/teams/{id}/players
     */
    public function players(Request $request, int $id): JsonResponse
    {
        try {
            // Verify team exists
            $team = Team::find($id);
            if (!$team) {
                return $this->errorResponse('Team not found', 404, null, 'TEAM_NOT_FOUND');
            }

            // Validate query parameters
            $validated = $request->validate([
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            $page = $validated['page'] ?? 1;
            $perPage = $validated['per_page'] ?? 20;

            $cacheKey = $this->cacheService->generateKey(
                "team:{$id}:players",
                ['page' => $page, 'per_page' => $perPage]
            );

            $data = $this->cacheService->remember(
                $cacheKey,
                600, // 10 minutes
                function () use ($id, $page, $perPage) {
                    return $this->fetchTeamPlayers($id, $page, $perPage);
                },
                [
                    'public-api',
                    'teams',
                    "team:{$id}",
                    "public:team:{$id}:players",
                ],
                'live'
            );

            return $this->successResponse($data, 'Team players retrieved successfully', 200, 600);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Invalid query parameters', 422, $e->errors(), 'VALIDATION_ERROR');
        } catch (Throwable $e) {
            Log::error('Failed to retrieve team players', [
                'team_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->handleServiceFailure($e, 'Failed to retrieve team players', 'TeamService');
        }
    }

    /**
     * List all matches for a team
     *
     * GET /api/public/teams/{id}/matches
     */
    public function matches(Request $request, int $id): JsonResponse
    {
        try {
            // Verify team exists
            $team = Team::find($id);
            if (!$team) {
                return $this->errorResponse('Team not found', 404, null, 'TEAM_NOT_FOUND');
            }

            // Validate query parameters
            $validated = $request->validate([
                'status' => 'nullable|string|in:completed,upcoming,ongoing',
                'limit' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
            ]);

            $filters = array_filter([
                'status' => $validated['status'] ?? null,
                'limit' => $validated['limit'] ?? null,
                'page' => $validated['page'] ?? null,
            ]);

            $cacheKey = $this->cacheService->generateKey(
                "team:{$id}:matches",
                $filters
            );

            $data = $this->cacheService->remember(
                $cacheKey,
                300, // 5 minutes
                function () use ($id, $filters) {
                    return $this->fetchTeamMatches($id, $filters);
                },
                [
                    'public-api',
                    'teams',
                    "team:{$id}",
                    "public:team:{$id}:matches",
                ],
                'live'
            );

            return $this->successResponse($data, 'Team matches retrieved successfully', 200, 300);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse('Invalid query parameters', 422, $e->errors(), 'VALIDATION_ERROR');
        } catch (Throwable $e) {
            Log::error('Failed to retrieve team matches', [
                'team_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->handleServiceFailure($e, 'Failed to retrieve team matches', 'TeamService');
        }
    }

    /**
     * Fetch teams for a tournament
     */
    protected function fetchTournamentTeams(int $tournamentId, int $page, int $perPage): array
    {
        $teams = Team::query()
            ->select(['id', 'tournament_id', 'name', 'logo', 'created_at', 'updated_at'])
            ->where('tournament_id', $tournamentId)
            ->withCount('players')
            ->orderBy('name')
            ->paginate($perPage, ['*'], 'page', $page);

        // Get match stats for each team (W/L/D counts)
        $teamIds = $teams->pluck('id')->toArray();
        $matchStats = $this->getTeamMatchStats($teamIds);

        $data = $teams->map(function ($team) use ($matchStats) {
            $stats = $matchStats[$team->id] ?? ['wins' => 0, 'losses' => 0, 'draws' => 0];

            return [
                'id' => $team->id,
                'name' => $team->name,
                'logo' => $team->logo,
                'player_count' => $team->players_count,
                'match_stats' => $stats,
                'created_at' => $team->created_at->toISOString(),
                'updated_at' => $team->updated_at->toISOString(),
            ];
        });

        return [
            'teams' => $data->toArray(),
            'pagination' => [
                'current_page' => $teams->currentPage(),
                'last_page' => $teams->lastPage(),
                'per_page' => $teams->perPage(),
                'total' => $teams->total(),
                'from' => $teams->firstItem(),
                'to' => $teams->lastItem(),
            ],
        ];
    }

    /**
     * Fetch team details
     */
    protected function fetchTeamDetails(int $id): ?array
    {
        $team = Team::query()
            ->select(['id', 'tournament_id', 'name', 'logo', 'created_at', 'updated_at'])
            ->withCount('players')
            ->find($id);

        if (!$team) {
            return null;
        }

        // Get tournament info
        $tournament = $this->tournamentServiceClient->getPublicTournament($team->tournament_id);

        // Get basic statistics
        $matchStats = $this->getTeamMatchStats([$id]);
        $stats = $matchStats[$id] ?? ['wins' => 0, 'losses' => 0, 'draws' => 0];

        return [
            'id' => $team->id,
            'name' => $team->name,
            'logo' => $team->logo,
            'tournament' => $tournament ? [
                'id' => $tournament['id'] ?? null,
                'name' => $tournament['name'] ?? null,
                'status' => $tournament['status'] ?? null,
            ] : null,
            'player_count' => $team->players_count,
            'statistics' => [
                'wins' => $stats['wins'],
                'losses' => $stats['losses'],
                'draws' => $stats['draws'],
                'total_matches' => $stats['wins'] + $stats['losses'] + $stats['draws'],
            ],
            'created_at' => $team->created_at->toISOString(),
            'updated_at' => $team->updated_at->toISOString(),
        ];
    }

    /**
     * Fetch team players
     */
    protected function fetchTeamPlayers(int $teamId, int $page, int $perPage): array
    {
        $players = Player::query()
            ->select(['id', 'team_id', 'full_name', 'position', 'jersey_number'])
            ->where('team_id', $teamId)
            ->orderBy('jersey_number')
            ->orderBy('full_name')
            ->paginate($perPage, ['*'], 'page', $page);

        $data = $players->map(function ($player) {
            return [
                'id' => $player->id,
                'full_name' => $player->full_name,
                'position' => $player->position,
                'jersey_number' => $player->jersey_number,
            ];
        });

        return [
            'players' => $data->toArray(),
            'pagination' => [
                'current_page' => $players->currentPage(),
                'last_page' => $players->lastPage(),
                'per_page' => $players->perPage(),
                'total' => $players->total(),
                'from' => $players->firstItem(),
                'to' => $players->lastItem(),
            ],
        ];
    }

    /**
     * Fetch team matches
     */
    protected function fetchTeamMatches(int $teamId, array $filters): array
    {
        try {
            $matches = $this->matchServiceClient->getTeamMatches($teamId, $filters);

            if (!$matches) {
                return [
                    'matches' => [],
                    'pagination' => [
                        'total' => 0,
                        'per_page' => $filters['limit'] ?? 10,
                        'current_page' => $filters['page'] ?? 1,
                    ],
                ];
            }

            // Format matches for public API
            $formattedMatches = collect($matches)->map(function ($match) use ($teamId) {
                $isHome = ($match['home_team_id'] ?? null) == $teamId;
                $opponent = $isHome
                    ? ($match['away_team'] ?? ['id' => null, 'name' => 'Unknown Team'])
                    : ($match['home_team'] ?? ['id' => null, 'name' => 'Unknown Team']);

                return [
                    'id' => $match['id'] ?? null,
                    'date' => $match['match_date'] ?? $match['date'] ?? null,
                    'status' => $match['status'] ?? 'scheduled',
                    'opponent' => [
                        'id' => $opponent['id'] ?? null,
                        'name' => $opponent['name'] ?? 'Unknown Team',
                    ],
                    'is_home' => $isHome,
                    'score' => [
                        'team' => $isHome ? ($match['home_score'] ?? null) : ($match['away_score'] ?? null),
                        'opponent' => $isHome ? ($match['away_score'] ?? null) : ($match['home_score'] ?? null),
                    ],
                    'result' => $this->determineMatchResult(
                        $isHome ? ($match['home_score'] ?? null) : ($match['away_score'] ?? null),
                        $isHome ? ($match['away_score'] ?? null) : ($match['home_score'] ?? null),
                        $match['status'] ?? 'scheduled'
                    ),
                ];
            })->toArray();

            return [
                'matches' => $formattedMatches,
                'pagination' => [
                    'total' => count($formattedMatches),
                    'per_page' => $filters['limit'] ?? count($formattedMatches),
                    'current_page' => $filters['page'] ?? 1,
                ],
            ];
        } catch (Throwable $e) {
            Log::warning('Failed to fetch matches from match service', [
                'team_id' => $teamId,
                'error' => $e->getMessage(),
            ]);

            return [
                'matches' => [],
                'pagination' => ['total' => 0, 'per_page' => 10, 'current_page' => 1],
            ];
        }
    }

    /**
     * Get match statistics for teams (W/L/D counts)
     */
    protected function getTeamMatchStats(array $teamIds): array
    {
        // This would ideally come from match service or results service
        // For now, return empty stats (can be enhanced later)
        $stats = [];

        foreach ($teamIds as $teamId) {
            $stats[$teamId] = [
                'wins' => 0,
                'losses' => 0,
                'draws' => 0,
            ];
        }

        // TODO: Call match service or results service to get actual stats
        // For now, return placeholder data

        return $stats;
    }

    /**
     * Determine match result for a team
     */
    protected function determineMatchResult(?int $teamScore, ?int $opponentScore, string $status): string
    {
        if ($status !== 'completed' || $teamScore === null || $opponentScore === null) {
            return 'pending';
        }

        if ($teamScore > $opponentScore) {
            return 'win';
        } elseif ($teamScore < $opponentScore) {
            return 'loss';
        } else {
            return 'draw';
        }
    }
}
