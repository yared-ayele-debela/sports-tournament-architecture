<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Player;
use App\Services\AuthServiceClient;
use App\Services\TournamentServiceClient;
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

class TeamController extends Controller
{
    protected $authService;
    protected $tournamentService;

    public function __construct(AuthServiceClient $authService, TournamentServiceClient $tournamentService)
    {
        $this->authService = $authService;
        $this->tournamentService = $tournamentService;
    }

    public function public_index(Request $request): JsonResponse
    {
        $query = Team::with(['players', 'coaches']);

        if ($request->has('tournament_id')) {
            $query->where('tournament_id', $request->tournament_id);
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
    public function index(Request $request): JsonResponse
    {
        $query = Team::with(['players', 'coaches']);

        if ($request->has('tournament_id')) {
            $query->where('tournament_id', $request->tournament_id);
        }

        // If user is coach, only show their teams
        if (AuthHelper::isCoach()) {
            $query->whereHas('coaches', function ($q) {
                $q->where('user_id', AuthHelper::getCurrentUserId());
            });
        }

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $teams = $query->orderByDesc('id')->paginate($perPage);

        return ApiResponse::paginated($teams, 'Teams retrieved successfully');
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
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Validate tournament exists
            $tournamentResponse = $this->tournamentService->validateTournament($request->tournament_id);
            if (!($tournamentResponse['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid tournament'
                ], 400);
            }

            // Validate coach exists
            $coachResponse = $this->authService->validateUser($request->coach_id);
            if (!($coachResponse['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid coach'
                ], 400);
            }

            DB::beginTransaction();

            // Create team
            $team = Team::create([
                'tournament_id' => $request->tournament_id,
                'name' => $request->name,
                'logo' => $request->logo,
            ]);

            // Attach coach to team
            $team->coaches()->attach($request->coach_id);

            // Load relationships
            $team->load('coaches');

            DB::commit();

            // Fire event
            event(new TeamCreated($team, $request->coach_id));

            return response()->json([
                'success' => true,
                'message' => 'Team created successfully',
                'data' => $team
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create team: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        $team = Team::with(['players', 'coaches'])->find($id);

        if (!$team) {
            return response()->json([
                'success' => false,
                'message' => 'Team not found'
            ], 404);
        }

        // Check authorization for coaches
        if (AuthHelper::isCoach() && !$team->isCoach(AuthHelper::getCurrentUserId())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $team
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $team = Team::find($id);

        if (!$team) {
            return response()->json([
                'success' => false,
                'message' => 'Team not found'
            ], 404);
        }

        // Check authorization
        if (!AuthHelper::canManageTeam($id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'logo' => 'sometimes|nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $team->update($request->only(['name', 'logo']));

            // Fire event
            event(new TeamUpdated($team, AuthHelper::getCurrentUserId()));

            return response()->json([
                'success' => true,
                'message' => 'Team updated successfully',
                'data' => $team->load('coaches')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update team: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $team = Team::find($id);

        if (!$team) {
            return response()->json([
                'success' => false,
                'message' => 'Team not found'
            ], 404);
        }

        // Only admin can delete teams
        if (!AuthHelper::isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin can delete teams.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Detach coaches
            $team->coaches()->detach();

            // Delete players
            $team->players()->delete();

            // Delete team
            $team->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Team deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete team: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get team overview
     */
    public function overview(string $id): JsonResponse
    {
        try {
            $team = Team::with(['players', 'coaches'])
                ->findOrFail($id);

            // Calculate team statistics
            $totalPlayers = $team->players->count();
            $activePlayers = $team->players()->count(); // Remove status filter for now
            
            return response()->json([
                'success' => true,
                'message' => 'Team overview retrieved successfully',
                'data' => [
                    'team' => $team,
                    'statistics' => [
                        'total_players' => $totalPlayers,
                        'active_players' => $activePlayers,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve team overview',
                'error' => $e->getMessage()
            ], 500);
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve team squad',
                'error' => $e->getMessage()
            ], 500);
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
            $matchServiceUrl = config('services.match_service.url', 'http://localhost:8004');
            $response = Http::get("{$matchServiceUrl}/api/public/matches", [
                'team_id' => $id,
                'per_page' => 50
            ]);
            
            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve matches from match service',
                    'error' => 'Match service unavailable'
                ], 503);
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
            
            return response()->json([
                'success' => true,
                'message' => 'Team matches retrieved successfully',
                'data' => $matches,
                'meta' => $matchData['meta'] ?? [],
                'team' => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'short_name' => $team->short_name
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve team matches',
                'error' => $e->getMessage()
            ], 500);
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
            
            return response()->json([
                'success' => true,
                'message' => 'Team statistics retrieved successfully',
                'data' => [
                    'team_id' => (int)$id,
                    'total_players' => $totalPlayers,
                    'team_name' => $team->name,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve team statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
