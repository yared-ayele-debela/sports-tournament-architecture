<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EventSubscriber;
use Illuminate\Support\Facades\Log;

class EventsListen extends Command
{
    protected EventSubscriber $eventSubscriber;

    public function __construct(EventSubscriber $eventSubscriber)
    {
        parent::__construct();
        $this->eventSubscriber = $eventSubscriber;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:listen';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Subscribe to Redis events and keep connection alive';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Starting Redis event subscriber...');

        try {
            $this->eventSubscriber->subscribeToMatchCompleted();

            // Keep the process running
            while (true) {
                sleep(1);
            }
        } catch (\Exception $e) {
            Log::error('Event subscriber failed', [
                'error' => $e->getMessage(),
            ]);
            
            return 1;
        }
    }
}
