<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\Public\PublicApiController;
use App\Models\MatchGame;
use App\Models\MatchEvent;
use App\Services\PublicCacheService;
use App\Services\Clients\TeamServiceClient;
use App\Services\Clients\TournamentServiceClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;
use Carbon\Carbon;

class PublicMatchController extends PublicApiController
{
    protected PublicCacheService $cacheService;
    protected TeamServiceClient $teamServiceClient;
    protected TournamentServiceClient $tournamentServiceClient;

    public function __construct(
        PublicCacheService $cacheService,
        TeamServiceClient $teamServiceClient,
        TournamentServiceClient $tournamentServiceClient
    ) {
        parent::__construct();
        $this->cacheService = $cacheService;
        $this->teamServiceClient = $teamServiceClient;
        $this->tournamentServiceClient = $tournamentServiceClient;
        $this->defaultCacheTags = ['public-api', 'matches'];
    }

    /**
     * GET /api/public/matches/live
     * All live matches (status: in_progress)
     */
    public function live(Request $request): JsonResponse
    {
        try {
            $cacheKey = $this->cacheService->generateKey('matches:live', $request->all());
            $tags = ['public:matches:live'];
            $ttl = 30; // Very short TTL for live data

            $data = $this->cacheService->remember($cacheKey, $ttl, function () {
                return $this->fetchLiveMatches();
            }, $tags, 'live');

            return $this->successResponse($data, 'Live matches retrieved successfully', 200, $ttl);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve live matches', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Failed to retrieve live matches', 500);
        }
    }

    /**
     * GET /api/public/tournaments/{tournamentId}/matches
     * All matches for a tournament
     */
    public function tournamentMatches(Request $request, int $tournamentId): JsonResponse
    {
        try {
            // Validate tournament exists
            $tournament = $this->tournamentServiceClient->getPublicTournament($tournamentId);
            if (!$tournament) {
                return $this->errorResponse('Tournament not found', 404, null, 'TOURNAMENT_NOT_FOUND');
            }

            $validator = Validator::make($request->all(), [
                'status' => 'nullable|in:scheduled,in_progress,completed,cancelled',
                'date' => 'nullable|date',
                'limit' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Invalid query parameters', 400, $validator->errors()->toArray());
            }

            $filters = $validator->validated();
            $cacheKey = $this->cacheService->generateKey("tournament:{$tournamentId}:matches", $filters);
            $tags = ["public:tournament:{$tournamentId}:matches"];
            $ttl = 300; // 5 minutes

            $data = $this->cacheService->remember($cacheKey, $ttl, function () use ($tournamentId, $filters) {
                return $this->fetchTournamentMatches($tournamentId, $filters);
            }, $tags, 'live');

            return $this->successResponse($data, 'Tournament matches retrieved successfully', 200, $ttl);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve tournament matches', [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Failed to retrieve tournament matches', 500);
        }
    }

    /**
     * GET /api/public/matches/{id}
     * Full match details
     */
    public function show(int $id): JsonResponse
    {
        try {
            $match = MatchGame::find($id);
            if (!$match) {
                return $this->errorResponse('Match not found', 404, null, 'MATCH_NOT_FOUND');
            }

            $cacheKey = $this->cacheService->generateKey("match:{$id}");
            $tags = ["public:match:{$id}"];
            $ttl = 120; // 2 minutes

            $data = $this->cacheService->remember($cacheKey, $ttl, function () use ($match) {
                return $this->fetchMatchDetails($match);
            }, $tags, 'live');

            return $this->successResponse($data, 'Match details retrieved successfully', 200, $ttl);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve match details', [
                'match_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Failed to retrieve match details', 500);
        }
    }

    /**
     * GET /api/public/matches/{id}/events
     * Match event timeline
     */
    public function events(Request $request, int $id): JsonResponse
    {
        try {
            $match = MatchGame::find($id);
            if (!$match) {
                return $this->errorResponse('Match not found', 404, null, 'MATCH_NOT_FOUND');
            }

            // Different TTL for live vs completed matches
            $isLive = $match->status === 'in_progress';
            $ttl = $isLive ? 60 : 3600; // 1 minute for live, 1 hour for completed

            $cacheKey = $this->cacheService->generateKey("match:{$id}:events");
            $tags = ["public:match:{$id}:events"];

            $data = $this->cacheService->remember($cacheKey, $ttl, function () use ($match) {
                return $this->fetchMatchEvents($match);
            }, $tags, $isLive ? 'live' : 'static');

            return $this->successResponse($data, 'Match events retrieved successfully', 200, $ttl);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve match events', [
                'match_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Failed to retrieve match events', 500);
        }
    }

    /**
     * GET /api/public/matches/today
     * All matches happening today
     */
    public function today(Request $request): JsonResponse
    {
        try {
            $today = Carbon::today()->toDateString();
            $cacheKey = $this->cacheService->generateKey('matches:today', ['date' => $today]);
            $tags = ['public:matches:today'];
            $ttl = 300; // 5 minutes

            $data = $this->cacheService->remember($cacheKey, $ttl, function () use ($today) {
                return $this->fetchTodayMatches($today);
            }, $tags, 'live');

            return $this->successResponse($data, 'Today\'s matches retrieved successfully', 200, $ttl);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve today\'s matches', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Failed to retrieve today\'s matches', 500);
        }
    }

    /**
     * GET /api/public/matches/upcoming
     * Upcoming matches (next 7 days)
     */
    public function upcoming(Request $request): JsonResponse
    {
        try {
            $cacheKey = $this->cacheService->generateKey('matches:upcoming');
            $tags = ['public:matches:upcoming'];
            $ttl = 600; // 10 minutes

            $data = $this->cacheService->remember($cacheKey, $ttl, function () {
                return $this->fetchUpcomingMatches();
            }, $tags, 'live');

            return $this->successResponse($data, 'Upcoming matches retrieved successfully', 200, $ttl);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve upcoming matches', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Failed to retrieve upcoming matches', 500);
        }
    }

    /**
     * Fetch live matches
     */
    protected function fetchLiveMatches(): array
    {
        $matches = MatchGame::where('status', 'in_progress')
            ->orderBy('match_date')
            ->get();

        $formattedMatches = [];
        foreach ($matches as $match) {
            $homeTeam = $this->teamServiceClient->getPublicTeam($match->home_team_id);
            $awayTeam = $this->teamServiceClient->getPublicTeam($match->away_team_id);
            $venue = $this->tournamentServiceClient->getPublicVenue($match->venue_id);
            $eventsCount = $match->matchEvents()->count();

            $formattedMatches[] = [
                'id' => $match->id,
                'tournament_id' => $match->tournament_id,
                'home_team' => $homeTeam ? [
                    'id' => $homeTeam['id'] ?? null,
                    'name' => $homeTeam['name'] ?? null,
                ] : null,
                'away_team' => $awayTeam ? [
                    'id' => $awayTeam['id'] ?? null,
                    'name' => $awayTeam['name'] ?? null,
                ] : null,
                'venue' => $venue ? [
                    'id' => $venue['id'] ?? null,
                    'name' => $venue['name'] ?? null,
                ] : null,
                'match_date' => $match->match_date?->toISOString(),
                'status' => $match->status,
                'home_score' => $match->home_score,
                'away_score' => $match->away_score,
                'current_minute' => $match->current_minute,
                'events_count' => $eventsCount,
            ];
        }

        return [
            'matches' => $formattedMatches,
            'count' => count($formattedMatches),
        ];
    }

    /**
     * Fetch tournament matches
     */
    protected function fetchTournamentMatches(int $tournamentId, array $filters): array
    {
        $query = MatchGame::where('tournament_id', $tournamentId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date'])) {
            $query->whereDate('match_date', $filters['date']);
        }

        $perPage = $filters['limit'] ?? 20;
        $page = $filters['page'] ?? 1;

        $matches = $query->orderBy('match_date', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $formattedMatches = [];
        foreach ($matches->items() as $match) {
            $homeTeam = $this->teamServiceClient->getPublicTeam($match->home_team_id);
            $awayTeam = $this->teamServiceClient->getPublicTeam($match->away_team_id);
            $venue = $this->tournamentServiceClient->getPublicVenue($match->venue_id);

            $formattedMatches[] = [
                'id' => $match->id,
                'home_team' => $homeTeam ? [
                    'id' => $homeTeam['id'] ?? null,
                    'name' => $homeTeam['name'] ?? null,
                ] : null,
                'away_team' => $awayTeam ? [
                    'id' => $awayTeam['id'] ?? null,
                    'name' => $awayTeam['name'] ?? null,
                ] : null,
                'venue' => $venue ? [
                    'id' => $venue['id'] ?? null,
                    'name' => $venue['name'] ?? null,
                ] : null,
                'match_date' => $match->match_date?->toISOString(),
                'status' => $match->status,
                'home_score' => $match->home_score,
                'away_score' => $match->away_score,
                'round_number' => $match->round_number,
            ];
        }

        return [
            'matches' => $formattedMatches,
            'pagination' => [
                'current_page' => $matches->currentPage(),
                'per_page' => $matches->perPage(),
                'total' => $matches->total(),
                'last_page' => $matches->lastPage(),
            ],
        ];
    }

    /**
     * Fetch match details
     */
    protected function fetchMatchDetails(MatchGame $match): array
    {
        $homeTeam = $this->teamServiceClient->getPublicTeam($match->home_team_id);
        $awayTeam = $this->teamServiceClient->getPublicTeam($match->away_team_id);
        $tournament = $this->tournamentServiceClient->getPublicTournament($match->tournament_id);
        $venue = $this->tournamentServiceClient->getPublicVenue($match->venue_id);

        // Get team players
        $homeTeamPlayers = $homeTeam ? $this->teamServiceClient->getPublicTeamPlayers($match->home_team_id) : null;
        $awayTeamPlayers = $awayTeam ? $this->teamServiceClient->getPublicTeamPlayers($match->away_team_id) : null;

        return [
            'id' => $match->id,
            'tournament' => $tournament ? [
                'id' => $tournament['id'] ?? null,
                'name' => $tournament['name'] ?? null,
            ] : null,
            'home_team' => $homeTeam ? [
                'id' => $homeTeam['id'] ?? null,
                'name' => $homeTeam['name'] ?? null,
                'players' => $homeTeamPlayers ?? [],
            ] : null,
            'away_team' => $awayTeam ? [
                'id' => $awayTeam['id'] ?? null,
                'name' => $awayTeam['name'] ?? null,
                'players' => $awayTeamPlayers ?? [],
            ] : null,
            'venue' => $venue ? [
                'id' => $venue['id'] ?? null,
                'name' => $venue['name'] ?? null,
                'address' => $venue['address'] ?? null,
            ] : null,
            'match_date' => $match->match_date?->toISOString(),
            'status' => $match->status,
            'home_score' => $match->home_score,
            'away_score' => $match->away_score,
            'current_minute' => $match->current_minute,
            'round_number' => $match->round_number,
            'referee_id' => $match->referee_id,
        ];
    }

    /**
     * Fetch match events
     */
    protected function fetchMatchEvents(MatchGame $match): array
    {
        $events = MatchEvent::where('match_id', $match->id)
            ->orderBy('minute')
            ->orderBy('id')
            ->get();

        $formattedEvents = [];
        // Cache team players to avoid multiple API calls for the same team
        $teamPlayersCache = [];

        foreach ($events as $event) {
            $team = $this->teamServiceClient->getPublicTeam($event->team_id);
            $player = null;

            if ($event->player_id && $event->team_id) {
                try {
                    // Get team players if not already cached
                    if (!isset($teamPlayersCache[$event->team_id])) {
                        $teamPlayersCache[$event->team_id] = $this->teamServiceClient->getPublicTeamPlayers($event->team_id);
                    }

                    // Find player in team's players list
                    $teamPlayers = $teamPlayersCache[$event->team_id];
                    if ($teamPlayers && is_array($teamPlayers)) {
                        foreach ($teamPlayers as $teamPlayer) {
                            if (isset($teamPlayer['id']) && $teamPlayer['id'] == $event->player_id) {
                                $player = $teamPlayer;
                                break;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Failed to fetch player for match event', [
                        'player_id' => $event->player_id,
                        'team_id' => $event->team_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $formattedEvents[] = [
                'id' => $event->id,
                'minute' => $event->minute,
                'event_type' => $event->event_type,
                'description' => $event->description,
                'team' => $team ? [
                    'id' => $team['id'] ?? null,
                    'name' => $team['name'] ?? null,
                ] : null,
                'player' => $player ? [
                    'id' => $player['id'] ?? null,
                    'full_name' => $player['full_name'] ?? null,
                    'jersey_number' => $player['jersey_number'] ?? null,
                ] : null,
            ];
        }

        return [
            'events' => $formattedEvents,
            'count' => count($formattedEvents),
        ];
    }

    /**
     * Fetch today's matches
     */
    protected function fetchTodayMatches(string $date): array
    {
        $matches = MatchGame::whereDate('match_date', $date)
            ->orderBy('match_date')
            ->get();

        $formattedMatches = [];
        foreach ($matches as $match) {
            $homeTeam = $this->teamServiceClient->getPublicTeam($match->home_team_id);
            $awayTeam = $this->teamServiceClient->getPublicTeam($match->away_team_id);
            $venue = $this->tournamentServiceClient->getPublicVenue($match->venue_id);

            $formattedMatches[] = [
                'id' => $match->id,
                'tournament_id' => $match->tournament_id,
                'home_team' => $homeTeam ? [
                    'id' => $homeTeam['id'] ?? null,
                    'name' => $homeTeam['name'] ?? null,
                ] : null,
                'away_team' => $awayTeam ? [
                    'id' => $awayTeam['id'] ?? null,
                    'name' => $awayTeam['name'] ?? null,
                ] : null,
                'venue' => $venue ? [
                    'id' => $venue['id'] ?? null,
                    'name' => $venue['name'] ?? null,
                ] : null,
                'match_date' => $match->match_date?->toISOString(),
                'status' => $match->status,
                'home_score' => $match->home_score,
                'away_score' => $match->away_score,
                'round_number' => $match->round_number,
            ];
        }

        return [
            'matches' => $formattedMatches,
            'count' => count($formattedMatches),
            'date' => $date,
        ];
    }

    /**
     * Fetch upcoming matches (next 7 days)
     */
    protected function fetchUpcomingMatches(): array
    {
        $startDate = Carbon::now();
        $endDate = Carbon::now()->addDays(7);

        $matches = MatchGame::where('status', 'scheduled')
            ->whereBetween('match_date', [$startDate, $endDate])
            ->orderBy('match_date')
            ->limit(50)
            ->get();

        $formattedMatches = [];
        foreach ($matches as $match) {
            $homeTeam = $this->teamServiceClient->getPublicTeam($match->home_team_id);
            $awayTeam = $this->teamServiceClient->getPublicTeam($match->away_team_id);
            $venue = $this->tournamentServiceClient->getPublicVenue($match->venue_id);
            $tournament = $this->tournamentServiceClient->getPublicTournament($match->tournament_id);

            $formattedMatches[] = [
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
                'venue' => $venue ? [
                    'id' => $venue['id'] ?? null,
                    'name' => $venue['name'] ?? null,
                ] : null,
                'match_date' => $match->match_date?->toISOString(),
                'status' => $match->status,
                'round_number' => $match->round_number,
            ];
        }

        return [
            'matches' => $formattedMatches,
            'count' => count($formattedMatches),
            'date_range' => [
                'start' => $startDate->toISOString(),
                'end' => $endDate->toISOString(),
            ],
        ];
    }
}
