<?php

namespace App\Console\Commands;

use App\Services\Events\EventSubscriber;
use App\Services\Events\Handlers\MonitoringEventHandler;
use App\Services\Events\Handlers\CacheInvalidationHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EventsListenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:listen {--timeout=0 : Timeout in seconds (0 for no timeout)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Gateway Service event listener daemon for monitoring and cache invalidation';

    /**
     * The event subscriber instance
     *
     * @var EventSubscriber
     */
    protected $subscriber;

    /**
     * The event handlers
     *
     * @var array
     */
    protected $handlers = [];

    /**
     * Create a new command instance.
     *
     * @param EventSubscriber $subscriber
     * @return void
     */
    public function __construct(EventSubscriber $subscriber)
    {
        parent::__construct();
        $this->subscriber = $subscriber;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting Gateway Service event listener daemon...');
        $this->info('Service: ' . config('app.name', 'gateway-service'));
        
        // Initialize handlers
        $this->initializeHandlers();
        
        // Get channels from config
        $channels = config('events.channels', []);
        
        if (empty($channels)) {
            $this->error('No channels configured in config/events.php');
            return 1;
        }

        $this->info('Listening to channels: ' . implode(', ', $channels));
        $this->info('Handlers loaded: ' . count($this->handlers));
        $this->info('Press Ctrl+C to stop');

        // Create handler function
        $handler = function ($event, $channel) {
            $this->handleEvent($event, $channel);
        };

        try {
            // Start listening
            $this->subscriber->subscribe($channels, $handler);
            
        } catch (\Exception $e) {
            $this->error('Event listener failed: ' . $e->getMessage());
            Log::error('Event listener command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }

    /**
     * Initialize event handlers
     *
     * @return void
     */
    protected function initializeHandlers(): void
    {
        // Initialize MonitoringEventHandler
        if (config('events.monitoring.enabled', true)) {
            $monitoringHandler = new MonitoringEventHandler();
            $this->handlers[] = $monitoringHandler;
            
            $this->info('Loaded handler: ' . get_class($monitoringHandler) . ' for events: ' . 
                       implode(', ', $monitoringHandler->getHandledEventTypes()));
        }

        // Initialize CacheInvalidationHandler
        if (config('events.cache_invalidation.enabled', true)) {
            $cacheHandler = new CacheInvalidationHandler();
            $this->handlers[] = $cacheHandler;
            
            $this->info('Loaded handler: ' . get_class($cacheHandler) . ' for events: ' . 
                       implode(', ', $cacheHandler->getHandledEventTypes()));
        }

        if (empty($this->handlers)) {
            $this->warn('No handlers enabled. Check config/events.php');
        }
    }

    /**
     * Handle incoming event
     *
     * @param array $event
     * @param string $channel
     * @return void
     */
    protected function handleEvent(array $event, string $channel): void
    {
        $eventType = $event['event_type'] ?? 'unknown';
        $eventId = $event['event_id'] ?? 'unknown';
        
        $this->line("Processing event: {$eventType} ({$eventId})");

        // Find handlers for this event type
        $handled = false;
        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($eventType)) {
                try {
                    $handler->handle($event);
                    $this->info("✓ Handled by: " . get_class($handler));
                    $handled = true;
                } catch (\Exception $e) {
                    $this->error("✗ Handler failed: " . get_class($handler) . " - " . $e->getMessage());
                    Log::error('Event handler failed', [
                        'handler' => get_class($handler),
                        'event_id' => $eventId,
                        'event_type' => $eventType,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        if (!$handled) {
            $this->warn("No handler found for event: {$eventType}");
        }
    }
}
