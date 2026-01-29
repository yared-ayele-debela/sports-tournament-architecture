<?php

namespace App\Events;

use App\Models\Team;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $teamData;
    public string $deletedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Team $team, string $deletedBy = 'system')
    {
        $this->teamData = [
            'id' => $team->id,
            'name' => $team->name,
            'sport_id' => $team->sport_id,
            'tournament_id' => $team->tournament_id,
            'players_count' => $team->players()->count(),
        ];
        $this->deletedBy = $deletedBy;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('team.'.$this->teamData['id']),
        ];
    }
}
