<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchGame;
use App\Services\MatchScheduler;
use App\Services\Queue\QueuePublisher;
use App\Services\Events\EventPayloadBuilder;
use App\Services\Clients\TeamServiceClient;
use App\Services\Clients\TournamentServiceClient;
use App\Support\ApiResponse;
use App\Helpers\AuthHelper;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MatchController extends Controller
{
    protected MatchScheduler $matchScheduler;
    protected QueuePublisher $queuePublisher;
    protected TeamServiceClient $teamServiceClient;
    protected TournamentServiceClient $tournamentServiceClient;

    public function __construct(
        MatchScheduler $matchScheduler,
        QueuePublisher $queuePublisher,
        TeamServiceClient $teamServiceClient,
        TournamentServiceClient $tournamentServiceClient
    ) {
        $this->matchScheduler = $matchScheduler;
        $this->queuePublisher = $queuePublisher;
        $this->teamServiceClient = $teamServiceClient;
        $this->tournamentServiceClient = $tournamentServiceClient;
    }

    /**
     * Display a listing of matches.
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MatchGame::with(['matchEvents', 'matchReport']);

        // Filters
        if ($request->has('tournament_id')) {
            $query->where('tournament_id', $request->tournament_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('team_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('home_team_id', $request->team_id)
                  ->orWhere('away_team_id', $request->team_id);
            });
        }

        // If user is coach, only show matches for their teams
        if (AuthHelper::isCoach()) {
            $teamIds = AuthHelper::getCoachTeamIds();
            if (!empty($teamIds)) {
                $query->where(function ($q) use ($teamIds) {
                    $q->whereIn('home_team_id', $teamIds)
                      ->orWhereIn('away_team_id', $teamIds);
                });
            } else {
                // If coach has no teams, return empty result
                $query->whereRaw('1 = 0');
            }
        }

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $paginator = $query->orderBy('match_date')->paginate($perPage);

        // Enrich matches with team and tournament data
        $items = collect($paginator->items())
            ->map(function ($match) {
                $homeTeam = $this->teamServiceClient->getPublicTeam($match->home_team_id);
                $awayTeam = $this->teamServiceClient->getPublicTeam($match->away_team_id);
                $tournament = $this->tournamentServiceClient->getPublicTournament($match->tournament_id);

                $match->home_team = $homeTeam ? [
                    'id' => $homeTeam['id'] ?? null,
                    'name' => $homeTeam['name'] ?? null,
                ] : null;
                $match->away_team = $awayTeam ? [
                    'id' => $awayTeam['id'] ?? null,
                    'name' => $awayTeam['name'] ?? null,
                ] : null;
                $match->tournament = $tournament ? [
                    'id' => $tournament['id'] ?? null,
                    'name' => $tournament['name'] ?? null,
                ] : null;

                return $match;
            })
            ->all();

        $paginator->setCollection(collect($items));

        return ApiResponse::paginated($paginator, 'Matches retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tournament_id' => 'required|integer',
            'venue_id' => 'required|integer',
            'home_team_id' => 'required|integer',
            'away_team_id' => 'required|integer|different:home_team_id',
            'referee_id' => 'required|integer',
            'match_date' => 'required|date',
            'round_number' => 'required|integer|min:1',
        ]);

        $match = MatchGame::create($validated);

        // Dispatch match created event to queue (default priority)
        $user = Auth::user();
        $this->dispatchMatchCreatedQueueEvent($match, [
            'id' => Auth::id() ?? null,
            'name' => $user?->name ?? 'System'
        ]);

        return ApiResponse::created($match->load(['matchEvents', 'matchReport']));
    }

    public function show(string $id): JsonResponse
    {
        $match = MatchGame::with(['matchEvents', 'matchReport'])
            ->findOrFail($id);

        // Check authorization for coaches - only allow access to their team's matches
        if (AuthHelper::isCoach() && !AuthHelper::isAdmin()) {
            $teamIds = AuthHelper::getCoachTeamIds();
            $hasAccess = in_array($match->home_team_id, $teamIds) || in_array($match->away_team_id, $teamIds);
            
            if (!$hasAccess) {
                return ApiResponse::forbidden('Unauthorized to view this match');
            }
        }

        // Load external data using service clients for better error handling
        $homeTeam = $this->teamServiceClient->getPublicTeam($match->home_team_id);
        $awayTeam = $this->teamServiceClient->getPublicTeam($match->away_team_id);
        $tournament = $this->tournamentServiceClient->getPublicTournament($match->tournament_id);
        $venue = $this->tournamentServiceClient->getPublicVenue($match->venue_id);

        $match->home_team = $homeTeam ? [
            'id' => $homeTeam['id'] ?? null,
            'name' => $homeTeam['name'] ?? null,
        ] : null;
        $match->away_team = $awayTeam ? [
            'id' => $awayTeam['id'] ?? null,
            'name' => $awayTeam['name'] ?? null,
        ] : null;
        $match->tournament = $tournament ? [
            'id' => $tournament['id'] ?? null,
            'name' => $tournament['name'] ?? null,
        ] : null;
        $match->venue = $venue ? [
            'id' => $venue['id'] ?? null,
            'name' => $venue['name'] ?? null,
        ] : null;

        return ApiResponse::success($match);
    }

    public function publicShow(string $id): JsonResponse
    {
        $match = MatchGame::with(['matchEvents', 'matchReport'])
            ->findOrFail($id);

        // Load external data using service clients for better error handling
        $homeTeam = $this->teamServiceClient->getPublicTeam($match->home_team_id);
        $awayTeam = $this->teamServiceClient->getPublicTeam($match->away_team_id);
        $tournament = $this->tournamentServiceClient->getPublicTournament($match->tournament_id);
        $venue = $this->tournamentServiceClient->getPublicVenue($match->venue_id);

        $match->home_team = $homeTeam ? [
            'id' => $homeTeam['id'] ?? null,
            'name' => $homeTeam['name'] ?? null,
        ] : null;
        $match->away_team = $awayTeam ? [
            'id' => $awayTeam['id'] ?? null,
            'name' => $awayTeam['name'] ?? null,
        ] : null;
        $match->tournament = $tournament ? [
            'id' => $tournament['id'] ?? null,
            'name' => $tournament['name'] ?? null,
        ] : null;
        $match->venue = $venue ? [
            'id' => $venue['id'] ?? null,
            'name' => $venue['name'] ?? null,
        ] : null;

        return ApiResponse::success($match);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $match = MatchGame::findOrFail($id);

        $validated = $request->validate([
            'venue_id' => 'sometimes|integer',
            'referee_id' => 'sometimes|integer',
            'match_date' => 'sometimes|date',
            'round_number' => 'sometimes|integer|min:1',
            'home_score' => 'sometimes|integer|min:0',
            'away_score' => 'sometimes|integer|min:0',
            'current_minute' => 'sometimes|integer|min:0|max:120',
        ]);


        $oldData = $match->toArray();
        $oldScore = ['home' => $match->home_score, 'away' => $match->away_score];
        $match->update($validated);

        // Dispatch match updated event to queue (default priority)
        $this->dispatchMatchUpdatedQueueEvent($match, $oldData);

        // If score changed, dispatch score updated event (high priority for live matches)
        if (isset($validated['home_score']) || isset($validated['away_score'])) {
            $newScore = ['home' => $match->home_score, 'away' => $match->away_score];
            if ($oldScore['home'] !== $newScore['home'] || $oldScore['away'] !== $newScore['away']) {
                $this->dispatchMatchScoreUpdatedQueueEvent($match, $oldScore, $newScore);
            }
        }

        return ApiResponse::success($match->load(['matchEvents', 'matchReport']));
    }

    public function destroy(string $id): JsonResponse
    {
        $match = MatchGame::findOrFail($id);
        $matchData = [
            'id' => $match->id,
            'tournament_id' => $match->tournament_id,
            'home_team_id' => $match->home_team_id,
            'away_team_id' => $match->away_team_id,
            'status' => $match->status,
        ];
        $user = Auth::user();
        $match->delete();

        // Dispatch match deleted event to queue
        $this->dispatchMatchDeletedQueueEvent($matchData, [
            'id' => Auth::id() ?? null,
            'name' => $user?->name ?? 'System'
        ]);

        return ApiResponse::success(null, 'Match deleted successfully', 204);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
            'current_minute' => 'sometimes|integer|min:0|max:120',
        ]);

        $match = MatchGame::findOrFail($id);
        $oldStatus = $match->status;
        $match->update($validated);

        // Dispatch match status changed event if status changed (high priority)
        if (isset($validated['status']) && $validated['status'] !== $oldStatus) {
            $this->dispatchMatchStatusChangedQueueEvent($match, $oldStatus);

            // If match started, dispatch match started event (high priority)
            if ($validated['status'] === 'in_progress' && $oldStatus !== 'in_progress') {
                $user = Auth::user();
                $this->dispatchMatchStartedQueueEvent($match, [
                    'id' => Auth::id() ?? null,
                    'name' => $user?->name ?? 'System'
                ]);
            }
        }

        return ApiResponse::success($match);
    }

    public function generateSchedule(string $tournamentId): JsonResponse
    {
        try {
            $schedule = $this->matchScheduler->generateRoundRobin((int)$tournamentId);
            return ApiResponse::created($schedule, 'Schedule generated successfully');
        } catch (\Exception $e) {
            return ApiResponse::badRequest('Failed to generate schedule: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Get currently live/recent matches.
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
    public function liveMatches(Request $request): JsonResponse
    {
        $query = MatchGame::with(['matchEvents', 'matchReport'])
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->where('match_date', '>=', now()->subHours(2))
            ->orderBy('match_date');

        // If user is coach, only show matches for their teams
        if (AuthHelper::isCoach()) {
            $teamIds = AuthHelper::getCoachTeamIds();
            if (!empty($teamIds)) {
                $query->where(function ($q) use ($teamIds) {
                    $q->whereIn('home_team_id', $teamIds)
                      ->orWhereIn('away_team_id', $teamIds);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min(100, $perPage));

        $paginator = $query->paginate($perPage);

        // Enrich matches with team and tournament data
        $items = collect($paginator->items())
            ->map(function ($match) {
                $homeTeam = $this->teamServiceClient->getPublicTeam($match->home_team_id);
                $awayTeam = $this->teamServiceClient->getPublicTeam($match->away_team_id);
                $tournament = $this->tournamentServiceClient->getPublicTournament($match->tournament_id);

                $match->home_team = $homeTeam ? [
                    'id' => $homeTeam['id'] ?? null,
                    'name' => $homeTeam['name'] ?? null,
                ] : null;
                $match->away_team = $awayTeam ? [
                    'id' => $awayTeam['id'] ?? null,
                    'name' => $awayTeam['name'] ?? null,
                ] : null;
                $match->tournament = $tournament ? [
                    'id' => $tournament['id'] ?? null,
                    'name' => $tournament['name'] ?? null,
                ] : null;

                return $match;
            })
            ->all();

        $paginator->setCollection(collect($items));

        return ApiResponse::paginated($paginator, 'Live matches retrieved successfully');
    }

    /**
     * Get upcoming matches.
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
    public function upcomingMatches(Request $request): JsonResponse
    {
        $query = MatchGame::with(['matchEvents', 'matchReport'])
            ->whereIn('status', ['scheduled'])
            ->where('match_date', '>', now());

        // Apply filters
        if ($request->has('tournament_id')) {
            $query->where('tournament_id', $request->tournament_id);
        }

        if ($request->has('team_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('home_team_id', $request->team_id)
                  ->orWhere('away_team_id', $request->team_id);
            });
        }

        // If user is coach, only show matches for their teams
        if (AuthHelper::isCoach()) {
            $teamIds = AuthHelper::getCoachTeamIds();
            if (!empty($teamIds)) {
                $query->where(function ($q) use ($teamIds) {
                    $q->whereIn('home_team_id', $teamIds)
                      ->orWhereIn('away_team_id', $teamIds);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $paginator = $query->orderBy('match_date')->paginate($perPage);

        // Enrich matches with team and tournament data
        $items = collect($paginator->items())
            ->map(function ($match) {
                $homeTeam = $this->teamServiceClient->getPublicTeam($match->home_team_id);
                $awayTeam = $this->teamServiceClient->getPublicTeam($match->away_team_id);
                $tournament = $this->tournamentServiceClient->getPublicTournament($match->tournament_id);

                $match->home_team = $homeTeam ? [
                    'id' => $homeTeam['id'] ?? null,
                    'name' => $homeTeam['name'] ?? null,
                ] : null;
                $match->away_team = $awayTeam ? [
                    'id' => $awayTeam['id'] ?? null,
                    'name' => $awayTeam['name'] ?? null,
                ] : null;
                $match->tournament = $tournament ? [
                    'id' => $tournament['id'] ?? null,
                    'name' => $tournament['name'] ?? null,
                ] : null;

                return $match;
            })
            ->all();

        $paginator->setCollection(collect($items));

        return ApiResponse::paginated($paginator, 'Upcoming matches retrieved successfully');
    }

    /**
     * Get completed matches.
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
    public function completedMatches(Request $request): JsonResponse
    {
        $query = MatchGame::with(['matchEvents', 'matchReport'])
            ->where('status', 'completed');

        // Apply filters
        if ($request->has('tournament_id')) {
            $query->where('tournament_id', $request->tournament_id);
        }

        if ($request->has('team_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('home_team_id', $request->team_id)
                  ->orWhere('away_team_id', $request->team_id);
            });
        }

        // If user is coach, only show matches for their teams
        if (AuthHelper::isCoach()) {
            $teamIds = AuthHelper::getCoachTeamIds();
            if (!empty($teamIds)) {
                $query->where(function ($q) use ($teamIds) {
                    $q->whereIn('home_team_id', $teamIds)
                      ->orWhereIn('away_team_id', $teamIds);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $paginator = $query->orderBy('match_date', 'desc')->paginate($perPage);

        // Enrich matches with team and tournament data
        $items = collect($paginator->items())
            ->map(function ($match) {
                $homeTeam = $this->teamServiceClient->getPublicTeam($match->home_team_id);
                $awayTeam = $this->teamServiceClient->getPublicTeam($match->away_team_id);
                $tournament = $this->tournamentServiceClient->getPublicTournament($match->tournament_id);

                $match->home_team = $homeTeam ? [
                    'id' => $homeTeam['id'] ?? null,
                    'name' => $homeTeam['name'] ?? null,
                ] : null;
                $match->away_team = $awayTeam ? [
                    'id' => $awayTeam['id'] ?? null,
                    'name' => $awayTeam['name'] ?? null,
                ] : null;
                $match->tournament = $tournament ? [
                    'id' => $tournament['id'] ?? null,
                    'name' => $tournament['name'] ?? null,
                ] : null;

                return $match;
            })
            ->all();

        $paginator->setCollection(collect($items));

        return ApiResponse::paginated($paginator, 'Completed matches retrieved successfully');
    }

    /**
     * Get matches for a given date.
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
    public function matchesByDate(Request $request, string $date): JsonResponse
    {
        try {
            // Validate date format
            $validatedDate = \DateTime::createFromFormat('Y-m-d', $date);
            if (!$validatedDate) {
                return ApiResponse::badRequest('Invalid date format. Use Y-m-d format.');
            }

            $query = MatchGame::with(['matchEvents', 'matchReport'])
                ->whereDate('match_date', $date);

            // Apply optional filters
            if ($request->has('tournament_id')) {
                $query->where('tournament_id', $request->tournament_id);
            }

            if ($request->has('team_id')) {
                $query->where(function ($q) use ($request) {
                    $q->where('home_team_id', $request->team_id)
                      ->orWhere('away_team_id', $request->team_id);
                });
            }

            $perPage = (int) $request->query('per_page', 20);
            $perPage = max(1, min(100, $perPage));

            $paginator = $query->orderBy('match_date')->paginate($perPage);

            // Enrich matches with team and tournament data
            $items = collect($paginator->items())
                ->map(function ($match) {
                    $homeTeam = $this->teamServiceClient->getPublicTeam($match->home_team_id);
                    $awayTeam = $this->teamServiceClient->getPublicTeam($match->away_team_id);
                    $tournament = $this->tournamentServiceClient->getPublicTournament($match->tournament_id);

                    $match->home_team = $homeTeam ? [
                        'id' => $homeTeam['id'] ?? null,
                        'name' => $homeTeam['name'] ?? null,
                    ] : null;
                    $match->away_team = $awayTeam ? [
                        'id' => $awayTeam['id'] ?? null,
                        'name' => $awayTeam['name'] ?? null,
                    ] : null;
                    $match->tournament = $tournament ? [
                        'id' => $tournament['id'] ?? null,
                        'name' => $tournament['name'] ?? null,
                    ] : null;

                    return $match;
                })
                ->all();

            $paginator->setCollection(collect($items));

            return ApiResponse::paginated($paginator, "Matches for {$date} retrieved successfully");
        } catch (\Exception $e) {
            Log::error('Failed to retrieve matches by date', [
                'date' => $date,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::serverError('Failed to retrieve matches by date', $e);
        }
    }

    /**
     * Dispatch match created event to queue (default priority)
     *
     * @param MatchGame $match
     * @param array $user
     * @return void
     */
    protected function dispatchMatchCreatedQueueEvent(MatchGame $match, array $user): void
    {
        try {
            $payload = EventPayloadBuilder::matchCreated($match, $user);
            $this->queuePublisher->dispatchNormal('events', $payload, 'match.created');
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch match created queue event', [
                'match_id' => $match->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Dispatch match updated event to queue (default priority)
     *
     * @param MatchGame $match
     * @param array $oldData
     * @return void
     */
    protected function dispatchMatchUpdatedQueueEvent(MatchGame $match, array $oldData): void
    {
        try {
            $payload = EventPayloadBuilder::matchUpdated($match, $oldData);
            $this->queuePublisher->dispatchNormal('events', $payload, 'match.updated');
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch match updated queue event', [
                'match_id' => $match->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Dispatch match status changed event to queue (high priority)
     *
     * @param MatchGame $match
     * @param string $oldStatus
     * @return void
     */
    protected function dispatchMatchStatusChangedQueueEvent(MatchGame $match, string $oldStatus): void
    {
        try {
            $payload = EventPayloadBuilder::matchStatusChanged($match, $oldStatus);
            $this->queuePublisher->dispatchHigh('events', $payload, 'match.status.changed');
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch match status changed queue event', [
                'match_id' => $match->id,
                'old_status' => $oldStatus,
                'new_status' => $match->status,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Dispatch match started event to queue (high priority)
     *
     * @param MatchGame $match
     * @param array $user
     * @return void
     */
    protected function dispatchMatchStartedQueueEvent(MatchGame $match, array $user): void
    {
        try {
            $payload = EventPayloadBuilder::matchStarted($match, $user);
            $this->queuePublisher->dispatchHigh('events', $payload, 'match.started');
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch match started queue event', [
                'match_id' => $match->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Dispatch match score updated event to queue (high priority for live matches)
     *
     * @param MatchGame $match
     * @param array $oldScore
     * @param array $newScore
     * @return void
     */
    protected function dispatchMatchScoreUpdatedQueueEvent(MatchGame $match, array $oldScore, array $newScore): void
    {
        try {
            $payload = EventPayloadBuilder::matchScoreUpdated($match, $oldScore, $newScore);
            $this->queuePublisher->dispatchHigh('events', $payload, 'match.score.updated');
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch match score updated queue event', [
                'match_id' => $match->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Dispatch match deleted event to queue
     *
     * @param array $matchData
     * @param array $user
     * @return void
     */
    protected function dispatchMatchDeletedQueueEvent(array $matchData, array $user): void
    {
        try {
            $payload = EventPayloadBuilder::matchDeleted($matchData, $user);
            $this->queuePublisher->dispatchNormal('events', $payload, 'match.deleted');
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch match deleted queue event', [
                'match_id' => $matchData['id'] ?? null,
                'error' => $e->getMessage()
            ]);
        }
    }
}
