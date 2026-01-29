<?php

namespace App\Console\Commands;

use App\Listeners\SubscribeToTournamentEvents;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TournamentEventSubscriber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:tournament-subscribe';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscribe to tournament events from tournament-service via Redis';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting tournament event subscription...');
        $this->info('Listening for events on channels:');
        $this->info('  - sports.tournament.status.changed');
        $this->info('  - sports.tournament.created');
        
        try {
            $subscriber = new SubscribeToTournamentEvents();
            
            // This will block and listen continuously
            $subscriber->handle();
            
        } catch (\Exception $e) {
            Log::error('Tournament subscription failed', [
                'error' => $e->getMessage(),
            ]);
            
            $this->error('Subscription failed: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
