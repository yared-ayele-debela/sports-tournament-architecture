<?php

namespace App\Events;

use App\Models\MatchModel;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public MatchModel $match;
    public array $changes;

    public function __construct(MatchModel $match, array $changes = [])
    {
        $this->match = $match->load(['homeTeam', 'awayTeam']);
        $this->changes = $changes;
    }

    public function broadcastOn(): Channel
    {
        return new Channel("match.{$this->match->id}");
    }

    public function broadcastAs(): string
    {
        return 'match.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'match_id' => $this->match->id,
            'status' => $this->match->status,
            'home_score' => $this->match->home_score ?? 0,
            'away_score' => $this->match->away_score ?? 0,
            'current_minute' => $this->match->current_minute,
            'changes' => $this->changes,
            'updated_at' => $this->match->updated_at->toIso8601String(),
        ];
    }
}
