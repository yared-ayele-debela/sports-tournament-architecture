<?php

namespace App\Events;

use App\Models\MatchEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchEventCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public MatchEvent $event;

    public function __construct(MatchEvent $event)
    {
        $this->event = $event->load(['player', 'team']);
    }

    public function broadcastOn(): Channel
    {
        return new Channel("match.{$this->event->match_id}");
    }

    public function broadcastAs(): string
    {
        return 'event.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->event->id,
            'match_id' => $this->event->match_id,
            'minute' => $this->event->minute,
            'event_type' => $this->event->event_type,
            'description' => $this->event->description,
            'player' => $this->event->player ? [
                'id' => $this->event->player->id,
                'name' => $this->event->player->name,
            ] : null,
            'team' => [
                'id' => $this->event->team->id,
                'name' => $this->event->team->name,
            ],
            'created_at' => $this->event->created_at->toIso8601String(),
        ];
    }
}
