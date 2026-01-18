<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Team;
use App\Models\TeamCoach;
use App\Services\HttpClients\AuthServiceClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PlayerController extends Controller
{
    protected AuthServiceClient $authClient;

    public function __construct(AuthServiceClient $authClient)
    {
        $this->authClient = $authClient;
    }

    /**
     * Display a listing of players with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $teamId = $request->query('team_id');
            $tournamentId = $request->query('tournament_id');

            $query = Player::with('team');

            if ($teamId) {
                $query->where('team_id', $teamId);
            }

            if ($tournamentId) {
                $query->whereHas('team', function ($q) use ($tournamentId) {
                    $q->where('tournament_id', $tournamentId);
                });
            }

            // If user is a coach, only show players from their teams
            if ($user && !$this->isAdmin($user)) {
                $userTeamIds = TeamCoach::where('user_id', $user->id)->pluck('team_id');
                $query->whereIn('team_id', $userTeamIds);
            }

            $players = $query->get();

            return response()->json([
                'success' => true,
                'data' => $players
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching players', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch players'
            ], 500);
        }
    }

    /**
     * Store a newly created player.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'team_id' => 'required|integer|exists:teams,id',
                'full_name' => 'required|string|max:255',
                'position' => 'required|string|max:100',
                'jersey_number' => 'required|integer|min:1|max:99'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Check authorization
            $user = $request->user();
            $team = Team::find($validated['team_id']);
            
            if (!$this->canManageTeamPlayers($user, $team)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to add players to this team'
                ], 403);
            }

            // Check for unique jersey number within the team
            $existingPlayer = Player::where('team_id', $validated['team_id'])
                ->where('jersey_number', $validated['jersey_number'])
                ->first();

            if ($existingPlayer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jersey number already exists in this team'
                ], 422);
            }

            $player = Player::create($validated);

            return response()->json([
                'success' => true,
                'data' => $player->load('team')
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating player', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create player'
            ], 500);
        }
    }

    /**
     * Display the specified player.
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $user = $request->user();
            
            $player = Player::with('team')->find($id);

            if (!$player) {
                return response()->json([
                    'success' => false,
                    'message' => 'Player not found'
                ], 404);
            }

            // Check authorization
            if (!$this->canAccessPlayer($user, $player)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $player
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching player', [
                'player_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch player'
            ], 500);
        }
    }

    /**
     * Update the specified player.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $player = Player::find($id);

            if (!$player) {
                return response()->json([
                    'success' => false,
                    'message' => 'Player not found'
                ], 404);
            }

            // Check authorization
            $user = $request->user();
            if (!$this->canManagePlayer($user, $player)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'full_name' => 'sometimes|required|string|max:255',
                'position' => 'sometimes|required|string|max:100',
                'jersey_number' => [
                    'sometimes',
                    'required',
                    'integer',
                    'min:1',
                    'max:99',
                    Rule::unique('players')->where(function ($query) use ($player) {
                        return $query->where('team_id', $player->team_id)
                            ->where('id', '!=', $player->id);
                    })
                ]
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            $player->update($validated);

            return response()->json([
                'success' => true,
                'data' => $player->fresh()->load('team')
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating player', [
                'player_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update player'
            ], 500);
        }
    }

    /**
     * Remove the specified player.
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $player = Player::find($id);

            if (!$player) {
                return response()->json([
                    'success' => false,
                    'message' => 'Player not found'
                ], 404);
            }

            // Check authorization
            $user = $request->user();
            if (!$this->canManagePlayer($user, $player)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $player->delete();

            return response()->json([
                'success' => true,
                'message' => 'Player deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting player', [
                'player_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete player'
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
     * Check if user can access player.
     */
    private function canAccessPlayer($user, Player $player): bool
    {
        if (!$user) return false;
        
        // Admins can access all players
        if ($this->isAdmin($user)) {
            return true;
        }

        // Coaches can only access players from their own teams
        $userTeamIds = TeamCoach::where('user_id', $user->id)->pluck('team_id');
        return $userTeamIds->contains($player->team_id);
    }

    /**
     * Check if user can manage player.
     */
    private function canManagePlayer($user, Player $player): bool
    {
        if (!$user) return false;
        
        // Admins can manage all players
        if ($this->isAdmin($user)) {
            return true;
        }

        // Coaches can only manage players from their own teams
        $userTeamIds = TeamCoach::where('user_id', $user->id)->pluck('team_id');
        return $userTeamIds->contains($player->team_id);
    }

    /**
     * Check if user can manage team players.
     */
    private function canManageTeamPlayers($user, Team $team): bool
    {
        if (!$user) return false;
        
        // Admins can manage all teams
        if ($this->isAdmin($user)) {
            return true;
        }

        // Coaches can only manage their own teams
        $userTeamIds = TeamCoach::where('user_id', $user->id)->pluck('team_id');
        return $userTeamIds->contains($team->id);
    }
}
