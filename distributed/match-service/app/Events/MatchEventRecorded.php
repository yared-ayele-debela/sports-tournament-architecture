<?php

namespace App\Events;

use App\Models\MatchEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Match Event Recorded Broadcast Event
 *
 * Broadcasts when a match event (goal, card, substitution) is recorded
 * Public channel for real-time updates
 * Uses ShouldBroadcastNow for immediate broadcasting (not queued)
 */
class MatchEventRecorded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public MatchEvent $matchEvent;
    public array $matchData;

    /**
     * Create a new event instance.
     */
    public function __construct(MatchEvent $matchEvent, array $matchData = [])
    {
        $this->matchEvent = $matchEvent;
        $this->matchData = $matchData;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('match.' . $this->matchEvent->match_id),
            new Channel('match.events'),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'match.event.recorded';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'event' => [
                'id' => $this->matchEvent->id,
                'match_id' => $this->matchEvent->match_id,
                'team_id' => $this->matchEvent->team_id,
                'player_id' => $this->matchEvent->player_id,
                'event_type' => $this->matchEvent->event_type,
                'minute' => $this->matchEvent->minute,
                'description' => $this->matchEvent->description,
                'created_at' => $this->matchEvent->created_at?->toIso8601String(),
            ],
            'match' => $this->matchData,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
