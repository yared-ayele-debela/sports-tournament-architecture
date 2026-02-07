<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MatchTimerService;

class UpdateMatchMinutes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'matches:update-minutes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update current minute for all active matches';

    /**
     * Execute the console command.
     */
    public function handle(MatchTimerService $timerService)
    {
        $this->info('Updating match minutes...');

        $updated = $timerService->updateAllActiveMatches();

        if ($updated > 0) {
            $this->info("Updated {$updated} match(es).");
        } else {
            $this->info('No matches needed updating.');
        }

        return Command::SUCCESS;
    }
}
