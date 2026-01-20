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
use Illuminate\Support\Facades\Log;

class TeamUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $team;
    public $updatedBy;

    public function __construct(Team $team, $updatedBy = null)
    {
        $this->team = $team;
        $this->updatedBy = $updatedBy;

         Log::info('TeamCreated event updated', [
            'team_id' => $team->id,
            'team_name' => $team->name,
            'coach_id' => $updatedBy,
        ]);
    }

    public function broadcastOn()
    {
        return new PrivateChannel('team.' . $this->team->id);
    }
}
