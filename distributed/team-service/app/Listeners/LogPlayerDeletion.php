<?php

namespace App\Listeners;

use App\Events\PlayerDeleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogPlayerDeletion implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(PlayerDeleted $event): void
    {
        Log::info('Player deleted', [
            'player_id' => $event->playerData['id'],
            'player_name' => $event->playerData['name'],
            'team_id' => $event->playerData['team_id'],
            'jersey_number' => $event->playerData['jersey_number'],
            'position' => $event->playerData['position'],
            'deleted_by' => $event->deletedBy,
            'deleted_at' => now()->toISOString(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(PlayerDeleted $event, \Throwable $exception): void
    {
        Log::error('Failed to log player deletion', [
            'player_id' => $event->playerData['id'],
            'error' => $exception->getMessage(),
        ]);
    }
}
