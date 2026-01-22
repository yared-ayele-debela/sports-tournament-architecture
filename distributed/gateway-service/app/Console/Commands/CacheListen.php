<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EventSubscriber;
use Illuminate\Support\Facades\Log;

class CacheListen extends Command
{
    protected EventSubscriber $eventSubscriber;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:listen {--channel= : Subscribe to specific channel} {--all : Subscribe to all channels}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen to Redis events for cache invalidation';

    public function __construct(EventSubscriber $eventSubscriber)
    {
        parent::__construct();
        $this->eventSubscriber = $eventSubscriber;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $channel = $this->option('channel');
        $all = $this->option('all');

        if ($all) {
            $this->info('Starting cache invalidation listener for all channels...');
            $this->subscribeToAll();
        } elseif ($channel) {
            $this->info("Starting cache invalidation listener for channel: {$channel}");
            $this->subscribeToChannel($channel);
        } else {
            $this->error('Please specify either --channel or --all option');
            return 1;
        }

        return 0;
    }

    /**
     * Subscribe to all channels
     */
    protected function subscribeToAll(): void
    {
        try {
            Log::info('Cache listener started for all channels');
            
            $this->eventSubscriber->subscribeToAll();
            
        } catch (\Exception $e) {
            Log::error('Cache listener failed for all channels', [
                'error' => $e->getMessage(),
            ]);
            
            $this->error('Failed to start cache listener: ' . $e->getMessage());
        }
    }

    /**
     * Subscribe to specific channel
     */
    protected function subscribeToChannel(string $channel): void
    {
        try {
            Log::info("Cache listener started for channel: {$channel}");
            
            switch ($channel) {
                case 'match.completed':
                    $this->eventSubscriber->subscribeToMatchCompleted();
                    break;
                case 'standings.updated':
                    $this->eventSubscriber->subscribeToStandingsUpdated();
                    break;
                case 'team.updated':
                    $this->eventSubscriber->subscribeToTeamUpdated();
                    break;
                case 'tournament.updated':
                    $this->eventSubscriber->subscribeToTournamentUpdated();
                    break;
                default:
                    $this->error("Unknown channel: {$channel}");
                    Log::warning("Unknown channel requested", ['channel' => $channel]);
                    return;
            }
            
        } catch (\Exception $e) {
            Log::error("Cache listener failed for channel: {$channel}", [
                'error' => $e->getMessage(),
            ]);
            
            $this->error("Failed to start cache listener for {$channel}: " . $e->getMessage());
        }
    }
}
