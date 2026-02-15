<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchGame;
use App\Models\MatchEvent;
use App\Services\Clients\TeamServiceClient;
use App\Services\Queue\QueuePublisher;
use App\Services\Events\EventPayloadBuilder;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MatchEventController extends Controller
{
    protected TeamServiceClient $teamService;
    protected QueuePublisher $queuePublisher;

    public function __construct(TeamServiceClient $teamService, QueuePublisher $queuePublisher)
    {
        $this->teamService = $teamService;
        $this->queuePublisher = $queuePublisher;
    }

    public function match_event_index(string $matchId): JsonResponse
    {
        $events = MatchEvent::where('match_id', $matchId)
            ->with('match')
            ->orderBy('minute')
            ->get();

        return ApiResponse::success($events);
    }


    public function index(string $matchId): JsonResponse
    {
        $events = MatchEvent::where('match_id', $matchId)
            ->with('match')
            ->orderBy('minute')
            ->get();

        return ApiResponse::success($events);
    }

    public function store(Request $request, string $matchId): JsonResponse
    {
        $match = MatchGame::findOrFail($matchId);

        $validated = $request->validate([
            'team_id' => 'required|integer',
            'player_id' => 'required|integer',
            'event_type' => 'required|in:goal,yellow_card,red_card,substitution',
            'minute' => 'required|integer|min:0|max:120',
            'description' => 'nullable|string|max:255',
        ]);

        // Validate player belongs to team
        if (!$this->validatePlayerTeam($validated['player_id'], $validated['team_id'])) {
            return ApiResponse::error(
                "Player (ID: {$validated['player_id']}) does not belong to the specified team (ID: {$validated['team_id']}). Please select a valid player for this team.",
                422
            );
        }

        // Validate team is participating in the match
        if (!in_array($validated['team_id'], [$match->home_team_id, $match->away_team_id])) {
            return ApiResponse::error('Team is not participating in this match', 422);
        }

        $validated['match_id'] = $matchId;
        $event = MatchEvent::create($validated);

        // If goal, increment score
        if ($validated['event_type'] === 'goal') {
            if ($validated['team_id'] == $match->home_team_id) {
                $match->home_score = ($match->home_score ?? 0) + 1;
            } else {
                $match->away_score = ($match->away_score ?? 0) + 1;
            }
        }

        // Update current minute
        $match->current_minute = $validated['minute'];
        $match->save();

        // Dispatch match event recorded event to queue (high priority - real-time)
        $user = Auth::user();
        $this->dispatchMatchEventRecordedQueueEvent($event, [
            'id' => Auth::id() ?? null,
            'name' => $user?->name ?? 'System'
        ]);

        return ApiResponse::created($event->load('match'));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $event = MatchEvent::findOrFail($id);
        $match = $event->match;

        $validated = $request->validate([
            'team_id' => 'sometimes|integer',
            'player_id' => 'sometimes|integer',
            'event_type' => 'sometimes|in:goal,yellow_card,red_card,substitution',
            'minute' => 'sometimes|integer|min:0|max:120',
            'description' => 'nullable|string|max:255',
        ]);

        // If team_id or player_id is being updated, validate player belongs to team
        if (isset($validated['player_id']) || isset($validated['team_id'])) {
            $teamId = $validated['team_id'] ?? $event->team_id;
            $playerId = $validated['player_id'] ?? $event->player_id;

            if (!$this->validatePlayerTeam($playerId, $teamId)) {
                return ApiResponse::error(
                    "Player (ID: {$playerId}) does not belong to the specified team (ID: {$teamId}). Please select a valid player for this team.",
                    422
                );
            }
        }

        // If team_id is being updated, validate team is participating in the match
        if (isset($validated['team_id'])) {
            if (!in_array($validated['team_id'], [$match->home_team_id, $match->away_team_id])) {
                return ApiResponse::error('Team is not participating in this match', 422);
            }
        }

        // Store old values for score adjustment
        $oldEventType = $event->event_type;
        $oldTeamId = $event->team_id;
        $wasGoal = $oldEventType === 'goal';
        $isGoal = ($validated['event_type'] ?? $oldEventType) === 'goal';

        // Update event
        $event->update($validated);
        $event->refresh(); // Refresh to get updated values

        // Handle score adjustment if goal status changed
        $newTeamId = $event->team_id; // Use refreshed event team_id
        if ($wasGoal && !$isGoal) {
            // Goal was removed - decrement score
            if ($oldTeamId == $match->home_team_id) {
                $match->home_score = max(0, ($match->home_score ?? 0) - 1);
            } else {
                $match->away_score = max(0, ($match->away_score ?? 0) - 1);
            }
        } elseif (!$wasGoal && $isGoal) {
            // Goal was added - increment score
            if ($newTeamId == $match->home_team_id) {
                $match->home_score = ($match->home_score ?? 0) + 1;
            } else {
                $match->away_score = ($match->away_score ?? 0) + 1;
            }
        } elseif ($wasGoal && $isGoal && $newTeamId != $oldTeamId) {
            // Goal moved from one team to another
            if ($oldTeamId == $match->home_team_id) {
                $match->home_score = max(0, ($match->home_score ?? 0) - 1);
                $match->away_score = ($match->away_score ?? 0) + 1;
            } else {
                $match->away_score = max(0, ($match->away_score ?? 0) - 1);
                $match->home_score = ($match->home_score ?? 0) + 1;
            }
        }

        // Update current minute if changed
        if (isset($validated['minute'])) {
            $match->current_minute = $validated['minute'];
        }

        $match->save();

        // Dispatch match event recorded event to queue (high priority - real-time)
        $user = Auth::user();
        $this->dispatchMatchEventRecordedQueueEvent($event->fresh(), [
            'id' => Auth::id() ?? null,
            'name' => $user?->name ?? 'System'
        ]);

        return ApiResponse::success($event->load('match'), 'Event updated successfully');
    }

    public function destroy(string $id): JsonResponse
    {
        $event = MatchEvent::findOrFail($id);
        $match = $event->match;

        // If deleting a goal, decrement score
        if ($event->event_type === 'goal') {
            if ($event->team_id == $match->home_team_id) {
                $match->home_score = max(0, ($match->home_score ?? 0) - 1);
            } else {
                $match->away_score = max(0, ($match->away_score ?? 0) - 1);
            }
            $match->save();
        }

        $event->delete();

        return ApiResponse::success(null, 'Event deleted successfully', 204);
    }

    protected function validatePlayerTeam(int $playerId, int $teamId): bool
    {
        try {
            $response = $this->teamService->validatePlayer($playerId, $teamId);
            return $response && isset($response['success']) && $response['success'] === true;
        } catch (\Exception $e) {
            // If validation fails (404 or other error), player doesn't belong to team
            Log::warning('Player validation failed', [
                'player_id' => $playerId,
                'team_id' => $teamId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Dispatch match event recorded event to queue (high priority - real-time)
     *
     * @param MatchEvent $matchEvent
     * @param array $user
     * @return void
     */
    protected function dispatchMatchEventRecordedQueueEvent(MatchEvent $matchEvent, array $user): void
    {
        try {
            $payload = EventPayloadBuilder::matchEventRecorded($matchEvent, $user);
            $this->queuePublisher->dispatchHigh('events', $payload, 'match.event.recorded');
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch match event recorded queue event', [
                'event_id' => $matchEvent->id,
                'match_id' => $matchEvent->match_id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
