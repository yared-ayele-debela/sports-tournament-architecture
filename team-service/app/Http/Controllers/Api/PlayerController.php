<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Team;
use App\Events\PlayerCreated;
use App\Events\PlayerUpdated;
use App\Services\Queue\QueuePublisher;
use App\Services\Events\EventPayloadBuilder;
use App\Services\PublicCacheService;
use App\Helpers\AuthHelper;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlayerController extends Controller
{
    protected QueuePublisher $queuePublisher;
    protected PublicCacheService $cacheService;

    public function __construct(QueuePublisher $queuePublisher, PublicCacheService $cacheService)
    {
        $this->queuePublisher = $queuePublisher;
        $this->cacheService = $cacheService;
    }

    /**
     * Display a listing of players.
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Player::with('team');

        // Support both global players listing and per-team listing
        // If called via /teams/{id}/players, use the route parameter as team_id
        $routeTeamId = $request->route('teamId') ?? $request->route('id');
        if ($routeTeamId) {
            $request->merge(['team_id' => (int) $routeTeamId]);
        }

        if ($request->has('team_id')) {
            $query->where('team_id', $request->team_id);
        }

        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('full_name', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('position', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        // If user is coach, only show players from their teams
        if (AuthHelper::isCoach()) {
            $coachUserId = AuthHelper::getCurrentUserId();
            // Get team IDs where this coach is assigned (using pivot table directly)
            $teamIds = DB::table('team_coach')
                ->where('user_id', $coachUserId)
                ->pluck('team_id')
                ->toArray();

            if (!empty($teamIds)) {
                $query->whereIn('team_id', $teamIds);
            } else {
                // If coach has no teams, return empty result
                $query->whereRaw('1 = 0');
            }

            // If team_id is provided (either query or route), verify coach has access to that team
            if ($request->has('team_id')) {
                $requestedTeamId = (int) $request->team_id;
                if (!in_array($requestedTeamId, $teamIds)) {
                    return ApiResponse::forbidden('Unauthorized to view players for this team');
                }
            }
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
            return ApiResponse::validationError($validator->errors());
        }

        // Check authorization - only admin or team coach can create players
        if (!AuthHelper::canManageTeam($request->team_id)) {
            return ApiResponse::forbidden('Unauthorized to create players for this team');
        }

        // Check jersey number uniqueness within team
        $existingPlayer = Player::where('team_id', $request->team_id)
            ->where('jersey_number', $request->jersey_number)
            ->first();

        if ($existingPlayer) {
            return ApiResponse::error('Jersey number already taken in this team', 422);
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

            // Immediately invalidate public API cache for this team's players
            $this->invalidatePlayerCache($player);

            // Fire legacy event
            event(new PlayerCreated($player, AuthHelper::getCurrentUserId()));

            return ApiResponse::created($player, 'Player created successfully');

        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to create player: ' . $e->getMessage(), $e);
        }
    }

    public function show(string $id): JsonResponse
    {
        $player = Player::with('team')->find($id);

        if (!$player) {
            return ApiResponse::notFound('Player not found');
        }

        // Check authorization for coaches
        if (AuthHelper::isCoach() && !$player->team->isCoach(AuthHelper::getCurrentUserId())) {
            return ApiResponse::forbidden('Unauthorized');
        }

        return ApiResponse::success($player);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $player = Player::with('team')->find($id);

        if (!$player) {
            return ApiResponse::notFound('Player not found');
        }

        // Check authorization
        if (!AuthHelper::canManageTeam($player->team_id)) {
            return ApiResponse::forbidden('Unauthorized to update this player');
        }

        $validator = Validator::make($request->all(), [
            'full_name' => 'sometimes|required|string|max:255',
            'position' => 'sometimes|required|in:Goalkeeper,Defender,Midfielder,Forward',
            'jersey_number' => 'sometimes|required|integer|min:1|max:99'
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors());
        }

        try {
            // Check jersey number uniqueness if being updated
            if ($request->has('jersey_number') && $request->jersey_number != $player->jersey_number) {
                $existingPlayer = Player::where('team_id', $player->team_id)
                    ->where('jersey_number', $request->jersey_number)
                    ->where('id', '!=', $player->id)
                    ->first();

                if ($existingPlayer) {
                    return ApiResponse::error('Jersey number already taken in this team', 422);
                }
            }

            $oldData = $player->toArray();
            $player->update($request->only(['full_name', 'position', 'jersey_number']));

            // Reload team relationship to ensure we have latest data
            $player->load('team');

            // Immediately invalidate public API cache for this team's players
            $this->invalidatePlayerCache($player);

            // Fire legacy event
            event(new PlayerUpdated($player, AuthHelper::getCurrentUserId()));

            return ApiResponse::success($player->load('team'), 'Player updated successfully');

        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to update player: ' . $e->getMessage(), $e);
        }
    }

    public function validatePlayer(string $teamId, string $playerId): JsonResponse
    {
        $player = Player::where('id', $playerId)
            ->where('team_id', $teamId)
            ->first();

        if (!$player) {
            return ApiResponse::notFound('Player not found or does not belong to this team');
        }

        return ApiResponse::success($player, 'Player validated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $player = Player::with('team')->find($id);

        if (!$player) {
            return ApiResponse::notFound('Player not found');
        }

        // Check authorization
        if (!AuthHelper::canManageTeam($player->team_id)) {
            return ApiResponse::forbidden('Unauthorized to delete this player');
        }

        try {
            // Store team info before deletion (needed for cache invalidation)
            $teamId = $player->team_id;
            $team = $player->team;

            $player->delete();

            // Immediately invalidate public API cache for this team's players
            $this->invalidatePlayerCacheForTeam($teamId, $team);

            return ApiResponse::success(null, 'Player deleted successfully');

        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to delete player: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Immediately invalidate public API cache for a player's team
     *
     * @param Player $player
     * @return void
     */
    protected function invalidatePlayerCache(Player $player): void
    {
        if (!$player->team) {
            $player->load('team');
        }

        $this->invalidatePlayerCacheForTeam($player->team_id, $player->team);
    }

    /**
     * Immediately invalidate public API cache for a team's players
     *
     * @param int $teamId
     * @param Team|null $team
     * @return void
     */
    protected function invalidatePlayerCacheForTeam(int $teamId, ?Team $team = null): void
    {
        try {
            $tags = [
                'public-api',
                'teams',
                'players',
                "team:{$teamId}",
                "public:team:{$teamId}",
                "public:team:{$teamId}:players",
            ];

            // Also invalidate tournament teams cache if tournament_id is available
            if ($team && $team->tournament_id) {
                $tags[] = "tournament:{$team->tournament_id}";
                $tags[] = "public:tournament:{$team->tournament_id}:teams";
            } elseif ($teamId) {
                // If team is not loaded, try to get tournament_id from database
                $team = Team::find($teamId);
                if ($team && $team->tournament_id) {
                    $tags[] = "tournament:{$team->tournament_id}";
                    $tags[] = "public:tournament:{$team->tournament_id}:teams";
                }
            }

            $this->cacheService->forgetByTags($tags);

            Log::info('Player cache invalidated immediately', [
                'team_id' => $teamId,
                'tournament_id' => $team?->tournament_id,
                'tags' => $tags
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to invalidate player cache immediately', [
                'team_id' => $teamId,
                'error' => $e->getMessage()
            ]);
            // Don't throw - cache invalidation failure shouldn't break the operation
        }
    }
}
