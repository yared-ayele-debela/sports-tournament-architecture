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

class PlayerUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $player;
    public $updatedBy;

    public function __construct(Player $player, $updatedBy = null)
    {
        $this->player = $player;
        $this->updatedBy = $updatedBy;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('team.' . $this->player->team_id);
    }
}
