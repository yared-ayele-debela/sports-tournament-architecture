<?php

namespace App\Jobs;

use App\Services\StandingsCalculator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RecalculateStandingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $tournamentId)
    {
    }

    public function handle(StandingsCalculator $standingsCalculator): void
    {
        Log::info('Recalculating standings for tournament: ' . $this->tournamentId);
        $standingsCalculator->recalculateForTournament($this->tournamentId);
    }
}
