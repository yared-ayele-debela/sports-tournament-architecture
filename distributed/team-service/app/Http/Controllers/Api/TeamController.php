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

            // Attach coach to team
            $team->coaches()->attach($request->coach_id);

            // Load relationships
            $team->load('coaches');

            DB::commit();

            // Fire event
            event(new TeamCreated($team, $request->coach_id));

            return ApiResponse::created($team, 'Team created successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return ApiResponse::serverError('Failed to create team: ' . $e->getMessage(), $e);
        }
    }

    public function show(string $id): JsonResponse
    {
        $team = Team::with(['players', 'coaches'])->find($id);

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
            $team->update($request->only(['name', 'logo']));

            // Fire event
            event(new TeamUpdated($team, AuthHelper::getCurrentUserId()));

            return ApiResponse::success($team->load('coaches'), 'Team updated successfully');

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

            // Detach coaches
            $team->coaches()->detach();

            // Delete players
            $team->players()->delete();

            // Delete team
            $team->delete();

            DB::commit();

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
            $team = Team::with(['players', 'coaches'])
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
            $matchServiceUrl = config('services.match_service.url', 'http://localhost:8004');
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
}
