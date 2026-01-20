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

class TeamCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $team;
    public $coachId;

    public function __construct(Team $team, $coachId = null)
    {
        $this->team = $team;
        $this->coachId = $coachId;

          Log::info('TeamCreated event instantiated', [
            'team_id' => $team->id,
            'team_name' => $team->name,
            'coach_id' => $coachId,
        ]);
    }

    public function broadcastOn()
    {
        return new PrivateChannel('team.' . $this->team->id);
    }
}
