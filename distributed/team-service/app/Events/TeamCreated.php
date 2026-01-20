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

class TeamCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $team;
    public $coachId;

    public function __construct(Team $team, $coachId = null)
    {
        $this->team = $team;
        $this->coachId = $coachId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('team.' . $this->team->id);
    }
}
