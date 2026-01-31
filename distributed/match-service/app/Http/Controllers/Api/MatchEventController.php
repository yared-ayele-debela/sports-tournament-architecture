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
            return ApiResponse::error('Player does not belong to the specified team', 422);
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

    public function destroy(string $id): JsonResponse
    {
        $event = MatchEvent::findOrFail($id);
        $event->delete();

        return ApiResponse::success(null, 'Event deleted successfully', 204);
    }

    protected function validatePlayerTeam(int $playerId, int $teamId): bool
    {
        $response = $this->teamService->validatePlayer($playerId, $teamId);
        return $response && isset($response['success']) && $response['success'] === true;
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
