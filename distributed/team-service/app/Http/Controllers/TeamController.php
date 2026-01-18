<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamCoach;
use App\Services\HttpClients\AuthServiceClient;
use App\Services\HttpClients\TournamentServiceClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TeamController extends Controller
{
    protected AuthServiceClient $authClient;
    protected TournamentServiceClient $tournamentClient;

    public function __construct(AuthServiceClient $authClient, TournamentServiceClient $tournamentClient)
    {
        $this->authClient = $authClient;
        $this->tournamentClient = $tournamentClient;
    }

    /**
     * Display a listing of teams with optional tournament filter.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $tournamentId = $request->query('tournament_id');

            $query = Team::with(['players', 'coaches']);

            if ($tournamentId) {
                $query->where('tournament_id', $tournamentId);
            }

            // If user is a coach, only show their teams
            if ($user && !$this->isAdmin($user)) {
                $userTeams = TeamCoach::where('user_id', $user->id)->pluck('team_id');
                $query->whereIn('id', $userTeams);
            }

            $teams = $query->get();

            return response()->json([
                'success' => true,
                'data' => $teams
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching teams', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch teams'
            ], 500);
        }
    }

    /**
     * Store a newly created team.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'tournament_id' => 'required|integer',
                'name' => 'required|string|max:255',
                'coach_name' => 'required|string|max:255',
                'logo' => 'nullable|string|max:500',
                'coach_user_id' => 'nullable|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Validate tournament exists
            if (!$this->tournamentClient->validateTournament($validated['tournament_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid tournament'
                ], 400);
            }

            // Validate coach if coach_user_id is provided
            if (isset($validated['coach_user_id'])) {
                if (!$this->authClient->validateUser($validated['coach_user_id'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid coach user'
                    ], 400);
                }
            }

            $team = Team::create($validated);

            // Create coach relationship if coach_user_id is provided
            if (isset($validated['coach_user_id'])) {
                TeamCoach::create([
                    'team_id' => $team->id,
                    'user_id' => $validated['coach_user_id']
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $team->load(['players', 'coaches'])
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating team', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create team'
            ], 500);
        }
    }

    /**
     * Display the specified team with players.
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            
            $team = Team::with(['players', 'coaches'])->find($id);

            if (!$team) {
                return response()->json([
                    'success' => false,
                    'message' => 'Team not found'
                ], 404);
            }

            // Check authorization
            if (!$this->canAccessTeam($user, $team)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $team
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching team', [
                'team_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch team'
            ], 500);
        }
    }

    /**
     * Update the specified team.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $team = Team::find($id);

            if (!$team) {
                return response()->json([
                    'success' => false,
                    'message' => 'Team not found'
                ], 404);
            }

            // Check authorization
            $user = $request->user();
            if (!$this->canUpdateTeam($user, $team)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'coach_name' => 'sometimes|required|string|max:255',
                'logo' => 'sometimes|nullable|string|max:500',
                'coach_user_id' => 'sometimes|nullable|integer'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Validate coach if coach_user_id is provided
            if (isset($validated['coach_user_id'])) {
                if (!$this->authClient->validateUser($validated['coach_user_id'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid coach user'
                    ], 400);
                }
            }

            $team->update($validated);

            // Update coach relationship if coach_user_id is provided
            if (isset($validated['coach_user_id'])) {
                TeamCoach::where('team_id', $team->id)->delete();
                TeamCoach::create([
                    'team_id' => $team->id,
                    'user_id' => $validated['coach_user_id']
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $team->fresh()->load(['players', 'coaches'])
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating team', [
                'team_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update team'
            ], 500);
        }
    }

    /**
     * Remove the specified team.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $team = Team::find($id);

            if (!$team) {
                return response()->json([
                    'success' => false,
                    'message' => 'Team not found'
                ], 404);
            }

            // Check authorization (only admins can delete teams)
            $user = $request->user();
            if (!$this->isAdmin($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $team->delete();

            return response()->json([
                'success' => true,
                'message' => 'Team deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting team', [
                'team_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete team'
            ], 500);
        }
    }

    /**
     * Check if user is admin.
     */
    private function isAdmin($user): bool
    {
        if (!$user) return false;
        
        return $this->authClient->userHasPermission($user->id, 'manage_all_teams');
    }

    /**
     * Check if user can access team.
     */
    private function canAccessTeam($user, Team $team): bool
    {
        if (!$user) return false;
        
        // Admins can access all teams
        if ($this->isAdmin($user)) {
            return true;
        }

        // Coaches can only access their own teams
        $userTeamIds = TeamCoach::where('user_id', $user->id)->pluck('team_id');
        return $userTeamIds->contains($team->id);
    }

    /**
     * Check if user can update team.
     */
    private function canUpdateTeam($user, Team $team): bool
    {
        if (!$user) return false;
        
        // Admins can update all teams
        if ($this->isAdmin($user)) {
            return true;
        }

        // Coaches can only update their own teams
        $userTeamIds = TeamCoach::where('user_id', $user->id)->pluck('team_id');
        return $userTeamIds->contains($team->id);
    }
}
