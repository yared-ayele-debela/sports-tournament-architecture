<?php

namespace App\Listeners;

use App\Events\PlayerUpdated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogPlayerUpdate implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(PlayerUpdated $event): void
    {
        Log::info('Player updated', [
            'player_id' => $event->player->id,
            'player_name' => $event->player->full_name,
            'team_id' => $event->player->team_id,
            'jersey_number' => $event->player->jersey_number,
            'position' => $event->player->position,
            'updated_by' => $event->updatedBy,
            'updated_at' => now()->toISOString(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(PlayerUpdated $event, \Throwable $exception): void
    {
        Log::error('Failed to log player update', [
            'player_id' => $event->player->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
