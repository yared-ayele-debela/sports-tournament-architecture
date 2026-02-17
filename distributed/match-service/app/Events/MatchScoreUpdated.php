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
 * Match Score Updated Broadcast Event
 *
 * Broadcasts when match score is updated
 * Public channel for real-time score updates
 * Uses ShouldBroadcastNow for immediate broadcasting (not queued)
 */
class MatchScoreUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public MatchGame $match;
    public array $oldScore;
    public array $newScore;

    /**
     * Create a new event instance.
     */
    public function __construct(MatchGame $match, array $oldScore, array $newScore)
    {
        $this->match = $match;
        $this->oldScore = $oldScore;
        $this->newScore = $newScore;
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
            new Channel('match.scores'),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'match.score.updated';
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
            'home_score' => $this->newScore['home'] ?? 0,
            'away_score' => $this->newScore['away'] ?? 0,
            'old_score' => $this->oldScore,
            'status' => $this->match->status,
            'current_minute' => $this->match->current_minute ?? 0,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
