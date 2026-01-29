<?php

namespace App\Listeners;

use App\Events\TeamDeleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogTeamDeletion implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(TeamDeleted $event): void
    {
        Log::info('Team deleted', [
            'team_id' => $event->teamData['id'],
            'team_name' => $event->teamData['name'],
            'sport_id' => $event->teamData['sport_id'],
            'tournament_id' => $event->teamData['tournament_id'],
            'players_count' => $event->teamData['players_count'],
            'deleted_by' => $event->deletedBy,
            'deleted_at' => now()->toISOString(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(TeamDeleted $event, \Throwable $exception): void
    {
        Log::error('Failed to log team deletion', [
            'team_id' => $event->teamData['id'],
            'error' => $exception->getMessage(),
        ]);
    }
}
