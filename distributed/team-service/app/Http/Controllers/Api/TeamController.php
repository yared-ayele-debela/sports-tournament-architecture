<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Player;
use App\Services\AuthServiceClient;
use App\Services\TournamentServiceClient;
use App\Services\Queue\QueuePublisher;
use App\Services\Events\EventPayloadBuilder;
use App\Events\TeamCreated;
use App\Events\TeamUpdated;
use App\Helpers\AuthHelper;
use App\Models\MatchGame;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TeamController extends Controller
{
    protected $authService;
    protected $tournamentService;
    protected QueuePublisher $queuePublisher;

    public function __construct(AuthServiceClient $authService, TournamentServiceClient $tournamentService, QueuePublisher $queuePublisher)
    {
        $this->authService = $authService;
        $this->tournamentService = $tournamentService;
        $this->queuePublisher = $queuePublisher;
    }

    public function public_index(Request $request): JsonResponse
    {
        $query = Team::with(['players']);

        if ($request->has('tournament_id')) {
            $query->where('tournament_id', $request->tournament_id);
        }

        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('logo', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $teams = $query->orderByDesc('id')->paginate($perPage);

        return ApiResponse::paginated($teams, 'Teams retrieved successfully');
    }
    /**
     * Display a listing of teams.
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
    public function index(Request $request, $tournamentId = null): JsonResponse
    {
        $query = Team::with(['players']);

        // Get tournament_id from route parameter or query parameter
        $tournamentId = $tournamentId ?? $request->route('tournamentId') ?? $request->input('tournament_id');

        if ($tournamentId) {
            $query->where('tournament_id', $tournamentId);
        }

        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('logo', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        // If user is coach, only show their teams (check pivot table directly since users are in auth-service)
        if (AuthHelper::isCoach()) {
            $coachUserId = AuthHelper::getCurrentUserId();
            $teamIds = DB::table('team_coach')
                ->where('user_id', $coachUserId)
                ->pluck('team_id')
                ->toArray();
            if (!empty($teamIds)) {
                $query->whereIn('id', $teamIds);
            } else {
                // If coach has no teams, return empty result
                $query->whereRaw('1 = 0');
            }
        }

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $paginator = $query->orderByDesc('id')->paginate($perPage);

        // Enrich teams with tournament name and coach names
        $items = collect($paginator->items())
            ->map(function ($team) {
                // Get tournament name
                $tournament = null;
                try {
                    $tournament = $this->tournamentService->getPublicTournament($team->tournament_id);
                    if (!$tournament) {
                        Log::warning('Tournament not found', [
                            'team_id' => $team->id,
                            'tournament_id' => $team->tournament_id
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to fetch tournament for team', [
                        'team_id' => $team->id,
                        'tournament_id' => $team->tournament_id,
                        'error' => $e->getMessage()
                    ]);
                }

                // Get coach names - fetch coach IDs directly from pivot table since users don't exist in team-service DB
                $coachNames = [];
                $coachIds = DB::table('team_coach')
                    ->where('team_id', $team->id)
                    ->pluck('user_id')
                    ->toArray();

                Log::info('Processing team coaches', [
                    'team_id' => $team->id,
                    'coaches_count' => count($coachIds),
                    'coach_ids' => $coachIds
                ]);

                if (!empty($coachIds)) {
                    foreach ($coachIds as $coachId) {
                        try {
                            // Fetch from auth-service to get the name
                            $coachData = $this->authService->getUser($coachId);

                            Log::info('Fetched coach data', [
                                'coach_id' => $coachId,
                                'team_id' => $team->id,
                                'has_data' => !is_null($coachData),
                                'has_name' => isset($coachData['name'])
                            ]);

                            if ($coachData && isset($coachData['name']) && !empty($coachData['name'])) {
                                $coachNames[] = $coachData['name'];
                            } else {
                                Log::warning('Coach user not found or has no name', [
                                    'coach_id' => $coachId,
                                    'team_id' => $team->id,
                                    'coach_data' => $coachData
                                ]);
                                $coachNames[] = 'Unknown';
                            }
                        } catch (\Exception $e) {
                            Log::error('Failed to fetch coach from auth-service', [
                                'coach_id' => $coachId,
                                'team_id' => $team->id,
                                'error' => $e->getMessage()
                            ]);
                            $coachNames[] = 'Unknown';
                        }
                    }
                } else {
                    Log::info('No coaches found for team', [
                        'team_id' => $team->id
                    ]);
                }

                // Set tournament data on model
                $tournamentData = $tournament ? [
                    'id' => $tournament['id'] ?? null,
                    'name' => $tournament['name'] ?? null,
                ] : null;

                $team->setAttribute('tournament', $tournamentData);
                $team->setAttribute('coaches_list', $coachNames);
                $team->setAttribute('coaches_count', count($coachNames));

                // Make sure these attributes are visible in JSON serialization
                $team->makeVisible(['tournament', 'coaches_list', 'coaches_count']);

                Log::info('Team enriched', [
                    'team_id' => $team->id,
                    'tournament_name' => $tournamentData['name'] ?? null,
                    'coaches_list' => $coachNames
                ]);

                return $team;
            })
            ->all();

        $paginator->setCollection(collect($items));

        return ApiResponse::paginated($paginator, 'Teams retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tournament_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'logo' => 'nullable|string|max:500',
            'coach_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors());
        }

        try {
            // Validate tournament exists
            $tournamentResponse = $this->tournamentService->validateTournament($request->tournament_id);
            if (!($tournamentResponse['success'] ?? false)) {
                return ApiResponse::badRequest('Invalid tournament');
            }

            // Validate coach exists
            $coachResponse = $this->authService->validateUser($request->coach_id);
            if (!($coachResponse['success'] ?? false)) {
                return ApiResponse::badRequest('Invalid coach');
            }

            DB::beginTransaction();

            // Create team
            $team = Team::create([
                'tournament_id' => $request->tournament_id,
                'name' => $request->name,
                'logo' => $request->logo,
            ]);

            // Attach coach to team (direct DB insert since users are in auth-service)
            // Use insertOrIgnore to avoid duplicate key errors
            DB::table('team_coach')->insertOrIgnore([
                'team_id' => $team->id,
                'user_id' => $request->coach_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            // Fire legacy event
            event(new TeamCreated($team, $request->coach_id));

            // Dispatch team created event to queue (default priority)
            $this->dispatchTeamCreatedQueueEvent($team, ['id' => $request->coach_id, 'name' => 'Coach']);

            return ApiResponse::created($team, 'Team created successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::serverError('Failed to create team: ' . $e->getMessage(), $e);
        }
    }

    public function show(string $id): JsonResponse
    {
        $team = Team::with(['players'])->find($id);

        if (!$team) {
            return ApiResponse::notFound('Team not found');
        }

        // Check authorization for coaches
        if (AuthHelper::isCoach() && !$team->isCoach(AuthHelper::getCurrentUserId())) {
            return ApiResponse::forbidden('Unauthorized');
        }

        return ApiResponse::success($team);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $team = Team::find($id);

        if (!$team) {
            return ApiResponse::notFound('Team not found');
        }

        // Check authorization
        if (!AuthHelper::canManageTeam($id)) {
            return ApiResponse::forbidden('Unauthorized');
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'logo' => 'sometimes|nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors());
        }

        try {
            $oldData = $team->toArray();
            $team->update($request->only(['name', 'logo']));

            // Fire legacy event
            event(new TeamUpdated($team, AuthHelper::getCurrentUserId()));

            // Dispatch team updated event to queue (default priority)
            $this->dispatchTeamUpdatedQueueEvent($team, $oldData);

            return ApiResponse::success($team, 'Team updated successfully');

        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to update team: ' . $e->getMessage(), $e);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $team = Team::find($id);

        if (!$team) {
            return ApiResponse::notFound('Team not found');
        }

        // Only admin can delete teams
        if (!AuthHelper::isAdmin()) {
            return ApiResponse::forbidden('Unauthorized. Only admin can delete teams.');
        }

        try {
            DB::beginTransaction();

            // Detach coaches (direct DB delete since users are in auth-service)
            DB::table('team_coach')->where('team_id', $team->id)->delete();

            // Delete players
            $team->players()->delete();

            // Delete team
            $team->delete();

            DB::commit();

            // Dispatch team deleted event to queue (default priority)
            $this->dispatchTeamDeletedQueueEvent($team, ['id' => AuthHelper::getCurrentUserId(), 'name' => 'Admin']);

            return ApiResponse::success(null, 'Team deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::serverError('Failed to delete team: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Get team overview
     */
    public function overview(string $id): JsonResponse
    {
        try {
            $team = Team::with(['players'])
                ->findOrFail($id);

            // Calculate team statistics
            $totalPlayers = $team->players->count();
            $activePlayers = $team->players()->count(); // Remove status filter for now

            return ApiResponse::success([
                'team' => $team,
                'statistics' => [
                    'total_players' => $totalPlayers,
                    'active_players' => $activePlayers,
                ]
            ], 'Team overview retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve team overview', $e);
        }
    }

    /**
     * Get team squad
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
    public function squad(Request $request, string $id): JsonResponse
    {
        try {
            $team = Team::findOrFail($id);

            $perPage = (int) $request->query('per_page', 20);
            $perPage = max(1, min(100, $perPage));

            $players = $team->players()->orderByDesc('id')->paginate($perPage);

            return ApiResponse::paginated($players, 'Team squad retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve team squad', $e);
        }
    }

    /**
     * Get team matches
     */
    public function matches(Request $request, string $id): JsonResponse
    {
        try {
            $team = Team::findOrFail($id);

            // Call match service to get team matches
            $matchServiceUrl = config('services.match_service.url', 'http://match-service:8004');
            $response = Http::get("{$matchServiceUrl}/api/public/matches", [
                'team_id' => $id,
                'per_page' => 50
            ]);

            if (!$response->successful()) {
                return ApiResponse::error('Failed to retrieve matches from match service', 503, 'Match service unavailable');
            }

            $matchData = $response->json();

            // Enrich match data with team information
            $matches = collect($matchData['data'] ?? [])->map(function ($match) use ($team) {
                $match['is_home'] = $match['home_team_id'] == $team->id;
                $match['opponent'] = $match['is_home'] ?
                    ($match['away_team'] ?? ['name' => 'Unknown Team']) :
                    ($match['home_team'] ?? ['name' => 'Unknown Team']);
                $match['team_score'] = $match['is_home'] ? $match['home_score'] : $match['away_score'];
                $match['opponent_score'] = $match['is_home'] ? $match['away_score'] : $match['home_score'];
                $match['result'] = $this->determineMatchResult($match['team_score'], $match['opponent_score'], $match['status']);
                return $match;
            });

            return ApiResponse::success([
                'matches' => $matches,
                'meta' => $matchData['meta'] ?? [],
                'team' => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'short_name' => $team->short_name
                ]
            ], 'Team matches retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve team matches', $e);
        }
    }

    /**
     * Determine match result for the team
     */
    private function determineMatchResult(?int $teamScore, ?int $opponentScore, string $status): string
    {
        if ($status !== 'completed' || is_null($teamScore) || is_null($opponentScore)) {
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

    /**
     * Get team statistics
     */
    public function statistics(string $id): JsonResponse
    {
        try {
            $team = Team::findOrFail($id);

            // Simple statistics based on players count
            $totalPlayers = $team->players()->count();

            return ApiResponse::success([
                'team_id' => (int)$id,
                'total_players' => $totalPlayers,
                'team_name' => $team->name,
            ], 'Team statistics retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve team statistics', $e);
        }
    }

    /**
     * Dispatch team created event to queue (default priority)
     *
     * @param Team $team
     * @param array $user
     * @return void
     */
    protected function dispatchTeamCreatedQueueEvent(Team $team, array $user): void
    {
        try {
            $payload = EventPayloadBuilder::teamCreated($team, $user);
            $this->queuePublisher->dispatchNormal('events', $payload, 'team.created');
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch team created queue event', [
                'team_id' => $team->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Dispatch team updated event to queue (default priority)
     *
     * @param Team $team
     * @param array $oldData
     * @return void
     */
    protected function dispatchTeamUpdatedQueueEvent(Team $team, array $oldData): void
    {
        try {
            $payload = EventPayloadBuilder::teamUpdated($team, $oldData);
            $this->queuePublisher->dispatchNormal('events', $payload, 'team.updated');
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch team updated queue event', [
                'team_id' => $team->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Dispatch team deleted event to queue (default priority)
     *
     * @param Team $team
     * @param array $user
     * @return void
     */
    protected function dispatchTeamDeletedQueueEvent(Team $team, array $user): void
    {
        try {
            $payload = EventPayloadBuilder::teamDeleted($team, $user);
            $this->queuePublisher->dispatchNormal('events', $payload, 'team.deleted');
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch team deleted queue event', [
                'team_id' => $team->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
