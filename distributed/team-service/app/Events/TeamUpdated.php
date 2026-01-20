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

class TeamUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $team;
    public $updatedBy;

    public function __construct(Team $team, $updatedBy = null)
    {
        $this->team = $team;
        $this->updatedBy = $updatedBy;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('team.' . $this->team->id);
    }
}
