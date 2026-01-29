<?php

namespace App\Listeners;

use App\Events\PlayerCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogPlayerCreation implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(PlayerCreated $event): void
    {
        Log::info('Player created', [
            'player_id' => $event->player->id,
            'player_name' => $event->player->full_name,
            'team_id' => $event->player->team_id,
            'jersey_number' => $event->player->jersey_number,
            'position' => $event->player->position,
            'created_by' => $event->createdBy,
            'created_at' => now()->toISOString(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(PlayerCreated $event, \Throwable $exception): void
    {
        Log::error('Failed to log player creation', [
            'player_id' => $event->player->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
