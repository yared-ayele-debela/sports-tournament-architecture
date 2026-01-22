<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Team;
use App\Events\PlayerCreated;
use App\Events\PlayerUpdated;
use App\Helpers\AuthHelper;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class PlayerController extends Controller
{
    public function __construct()
    {
    }

    /**
     * Display a listing of players.
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Player::with('team');

        if ($request->has('team_id')) {
            $query->where('team_id', $request->team_id);
        }

        // If user is coach, only show players from their teams
        if (AuthHelper::isCoach()) {
            $query->whereHas('team', function ($q) {
                $q->whereHas('coaches', function ($subQ) {
                    $subQ->where('user_id', AuthHelper::getCurrentUserId());
                });
            });
        }

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $players = $query->orderByDesc('id')->paginate($perPage);

        return ApiResponse::paginated($players, 'Players retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'team_id' => 'required|integer|exists:teams,id',
            'full_name' => 'required|string|max:255',
            'position' => 'required|in:Goalkeeper,Defender,Midfielder,Forward',
            'jersey_number' => 'required|integer|min:1|max:99'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check authorization - only admin or team coach can create players
        if (!AuthHelper::canManageTeam($request->team_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to create players for this team'
            ], 403);
        }

        // Check jersey number uniqueness within team
        $existingPlayer = Player::where('team_id', $request->team_id)
            ->where('jersey_number', $request->jersey_number)
            ->first();

        if ($existingPlayer) {
            return response()->json([
                'success' => false,
                'message' => 'Jersey number already taken in this team'
            ], 422);
        }

        try {
            $player = Player::create([
                'team_id' => $request->team_id,
                'full_name' => $request->full_name,
                'position' => $request->position,
                'jersey_number' => $request->jersey_number,
            ]);

            // Load relationships
            $player->load('team');

            // Fire event
            event(new PlayerCreated($player, AuthHelper::getCurrentUserId()));

            return response()->json([
                'success' => true,
                'message' => 'Player created successfully',
                'data' => $player
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create player: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        $player = Player::with('team')->find($id);

        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Player not found'
            ], 404);
        }

        // Check authorization for coaches
        if (AuthHelper::isCoach() && !$player->team->isCoach(AuthHelper::getCurrentUserId())) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $player
        ]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $player = Player::with('team')->find($id);

        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Player not found'
            ], 404);
        }

        // Check authorization
        if (!AuthHelper::canManageTeam($player->team_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this player'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|required|string|max:255',
            'position' => 'sometimes|required|in:Goalkeeper,Defender,Midfielder,Forward',
            'jersey_number' => 'sometimes|required|integer|min:1|max:99'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check jersey number uniqueness if being updated
            if ($request->has('jersey_number') && $request->jersey_number != $player->jersey_number) {
                $existingPlayer = Player::where('team_id', $player->team_id)
                    ->where('jersey_number', $request->jersey_number)
                    ->where('id', '!=', $player->id)
                    ->first();

                if ($existingPlayer) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Jersey number already taken in this team'
                    ], 422);
                }
            }

            $player->update($request->only(['full_name', 'position', 'jersey_number']));

            // Fire event
            event(new PlayerUpdated($player, AuthHelper::getCurrentUserId()));

            return response()->json([
                'success' => true,
                'message' => 'Player updated successfully',
                'data' => $player->load('team')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update player: ' . $e->getMessage()
            ], 500);
        }
    }

    public function validatePlayer(string $teamId, string $playerId): JsonResponse
    {
        $player = Player::where('id', $playerId)
            ->where('team_id', $teamId)
            ->first();

        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Player not found or does not belong to this team'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Player validated successfully',
            'data' => $player
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $player = Player::with('team')->find($id);

        if (!$player) {
            return response()->json([
                'success' => false,
                'message' => 'Player not found'
            ], 404);
        }

        // Check authorization
        if (!AuthHelper::canManageTeam($player->team_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this player'
            ], 403);
        }

        try {
            $player->delete();

            return response()->json([
                'success' => true,
                'message' => 'Player deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete player: ' . $e->getMessage()
            ], 500);
        }
    }
}
