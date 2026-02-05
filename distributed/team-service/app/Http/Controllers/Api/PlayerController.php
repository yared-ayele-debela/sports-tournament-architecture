<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Team;
use App\Events\PlayerCreated;
use App\Events\PlayerUpdated;
use App\Services\Queue\QueuePublisher;
use App\Services\Events\EventPayloadBuilder;
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

    public function __construct(QueuePublisher $queuePublisher)
    {
        $this->queuePublisher = $queuePublisher;
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

            // Fire legacy event
            event(new PlayerCreated($player, AuthHelper::getCurrentUserId()));
            
            // Dispatch player created event to queue (default priority)
            $this->dispatchPlayerCreatedQueueEvent($player, ['id' => AuthHelper::getCurrentUserId(), 'name' => 'User']);

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

            // Fire legacy event
            event(new PlayerUpdated($player, AuthHelper::getCurrentUserId()));
            
            // Dispatch player updated event to queue (default priority)
            $this->dispatchPlayerUpdatedQueueEvent($player, $oldData);

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
            $player->delete();

            // Dispatch player deleted event to queue (default priority)
            $this->dispatchPlayerDeletedQueueEvent($player, ['id' => AuthHelper::getCurrentUserId(), 'name' => 'User']);

            return ApiResponse::success(null, 'Player deleted successfully');

        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to delete player: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Dispatch player created event to queue (default priority)
     *
     * @param Player $player
     * @param array $user
     * @return void
     */
    protected function dispatchPlayerCreatedQueueEvent(Player $player, array $user): void
    {
        try {
            $payload = EventPayloadBuilder::playerCreated($player, $user);
            $this->queuePublisher->dispatchNormal('events', $payload, 'player.created');
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch player created queue event', [
                'player_id' => $player->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Dispatch player updated event to queue (default priority)
     *
     * @param Player $player
     * @param array $oldData
     * @return void
     */
    protected function dispatchPlayerUpdatedQueueEvent(Player $player, array $oldData): void
    {
        try {
            $payload = EventPayloadBuilder::playerUpdated($player, $oldData);
            $this->queuePublisher->dispatchNormal('events', $payload, 'player.updated');
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch player updated queue event', [
                'player_id' => $player->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Dispatch player deleted event to queue (default priority)
     *
     * @param Player $player
     * @param array $user
     * @return void
     */
    protected function dispatchPlayerDeletedQueueEvent(Player $player, array $user): void
    {
        try {
            $payload = EventPayloadBuilder::playerDeleted($player, $user);
            $this->queuePublisher->dispatchNormal('events', $payload, 'player.deleted');
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch player deleted queue event', [
                'player_id' => $player->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
