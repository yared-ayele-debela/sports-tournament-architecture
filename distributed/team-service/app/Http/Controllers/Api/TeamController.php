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
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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

        $teams = $query->get();

        return response()->json([
            'success' => true,
            'data' => $teams
        ]);
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
}
