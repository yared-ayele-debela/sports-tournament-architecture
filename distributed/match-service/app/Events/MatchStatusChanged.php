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
 * Match Status Changed Broadcast Event
 *
 * Broadcasts when match status changes (scheduled, in_progress, completed, etc.)
 * Public channel for real-time status updates
 * Uses ShouldBroadcastNow for immediate broadcasting (not queued)
 */
class MatchStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public MatchGame $match;
    public string $oldStatus;
    public string $newStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(MatchGame $match, string $oldStatus, string $newStatus)
    {
        $this->match = $match;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
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
        return 'match.status.changed';
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
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'current_minute' => $this->match->current_minute ?? 0,
            'home_score' => $this->match->home_score ?? 0,
            'away_score' => $this->match->away_score ?? 0,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
