<?php

namespace App\Events;

use App\Models\Player;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlayerDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $playerData;
    public string $deletedBy;

    /**
     * Create a new event instance.
     */
    public function __construct(Player $player, string $deletedBy = 'system')
    {
        $this->playerData = [
            'id' => $player->id,
            'name' => $player->full_name,
            'team_id' => $player->team_id,
            'jersey_number' => $player->jersey_number,
            'position' => $player->position,
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
            new PrivateChannel('team.'.$this->playerData['team_id']),
        ];
    }
}
