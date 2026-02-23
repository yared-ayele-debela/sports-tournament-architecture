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

class PlayerCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $player;
    public $createdBy;

    public function __construct(Player $player, $createdBy = null)
    {
        $this->player = $player;
        $this->createdBy = $createdBy;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('team.' . $this->player->team_id);
    }
}
