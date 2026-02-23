<?php

namespace App\Events;

use App\Models\MatchGame;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Match Minute Updated Broadcast Event
 *
 * Broadcasts when the current minute of a match is updated
 * Public channel for real-time match progress
 * Uses ShouldBroadcastNow for immediate broadcasting (not queued)
 */
class MatchMinuteUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public MatchGame $match;
    public int $currentMinute;

    /**
     * Create a new event instance.
     */
    public function __construct(MatchGame $match, int $currentMinute)
    {
        $this->match = $match;
        $this->currentMinute = $currentMinute;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('match.' . $this->match->id),
            new Channel('match.live'),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'match.minute.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'match_id' => $this->match->id,
            'current_minute' => $this->currentMinute,
            'status' => $this->match->status,
            'home_score' => $this->match->home_score ?? 0,
            'away_score' => $this->match->away_score ?? 0,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
