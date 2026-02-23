<?php

namespace App\Console\Commands;

use App\Services\Events\EventSubscriber;
use App\Contracts\EventHandler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Events Listen Command
 * 
 * Run event listeners as a daemon process
 */
class EventsListenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:listen {--channels=* : Specific channels to listen to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Listen for events from Redis Pub/Sub channels';

    /**
     * The event subscriber instance
     *
     * @var EventSubscriber
     */
    protected ?EventSubscriber $subscriber = null;

    /**
     * The event handlers
     *
     * @var array
     */
    protected array $handlers = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Starting event listener daemon...');
        $this->info('Service: ' . config('events.service.name', 'match-service'));

        // Load configuration
        $channels = $this->getChannels();
        $handlers = $this->getHandlers();

        if (empty($channels)) {
            $this->error('No channels configured for listening');
            return 1;
        }

        if (empty($handlers)) {
            $this->error('No handlers configured for events');
            return 1;
        }

        // Initialize handlers
        $this->initializeHandlers($handlers);

        // Create subscriber
        $this->subscriber = new EventSubscriber();

        // Setup signal handlers for graceful shutdown
        $this->registerSignalHandlers();

        $this->info('Listening to channels: ' . implode(', ', $channels));
        $this->info('Handlers loaded: ' . count($this->handlers));
        $this->info('Press Ctrl+C to stop');

        try {
            // Start listening
            $this->subscriber->subscribe($channels, [$this, 'handleEvent']);

            $this->info('Event listener stopped gracefully');
            return 0;

        } catch (\Exception $e) {
            $this->error('Event listener failed: ' . $e->getMessage());
            Log::error('Event listener command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Get channels to listen to
     *
     * @return array
     */
    protected function getChannels(): array
    {
        $optionChannels = $this->option('channels');
        
        if (!empty($optionChannels)) {
            return $optionChannels;
        }

        return config('events.channels', []);
    }

    /**
     * Get event handlers
     *
     * @return array
     */
    protected function getHandlers(): array
    {
        return config('events.handlers', []);
    }

    /**
     * Initialize event handlers
     *
     * @param array $handlerConfigs
     * @return void
     */
    protected function initializeHandlers(array $handlerConfigs): void
    {
        foreach ($handlerConfigs as $eventType => $handlers) {
            // $eventType is the event type (e.g., 'sports.tournament.created')
            // $handlers is an array of handler classes for this event type
            if (!is_array($handlers)) {
                $this->warn("Handlers for event type '{$eventType}' must be an array");
                continue;
            }

            foreach ($handlers as $handlerClass) {
                if (!class_exists($handlerClass)) {
                    $this->warn("Handler class not found: {$handlerClass}");
                    continue;
                }

                try {
                    $handler = app($handlerClass);
                    
                    if (!$handler instanceof EventHandler) {
                        $this->warn("Handler {$handlerClass} does not implement EventHandler interface");
                        continue;
                    }

                    // Register handler for this event type
                    $this->handlers[$eventType][] = $handler;

                    $this->info("Loaded handler: {$handlerClass} for event: {$eventType}");

                } catch (\Exception $e) {
                    $this->error("Failed to load handler {$handlerClass}: " . $e->getMessage());
                }
            }
        }
    }

    /**
     * Handle incoming event
     *
     * @param array $event
     * @param string $channel
     * @return void
     */
    public function handleEvent(array $event, string $channel): void
    {
        $eventType = $event['event_type'] ?? 'unknown';
        $eventId = $event['event_id'] ?? 'unknown';

        $this->line("Processing event: {$eventType} ({$eventId})");

        // Find handlers for this event type
        $handlers = $this->handlers[$eventType] ?? [];

        if (empty($handlers)) {
            $this->warn("No handlers found for event type: {$eventType}");
            return;
        }

        // Process event with each handler
        foreach ($handlers as $handler) {
            try {
                if ($handler->canHandle($eventType)) {
                    $handler->handle($event);
                    $this->line("✓ Handled by: " . get_class($handler));
                } else {
                    $this->warn("✗ Handler cannot handle event: " . get_class($handler));
                }
            } catch (\Exception $e) {
                $this->error("✗ Handler failed: " . get_class($handler) . " - " . $e->getMessage());
                Log::error('Event handler failed', [
                    'event_id' => $eventId,
                    'event_type' => $eventType,
                    'handler' => get_class($handler),
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Register signal handlers for graceful shutdown
     *
     * @return void
     */
    protected function registerSignalHandlers(): void
    {
        if (function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            
            pcntl_signal(SIGTERM, function($signal) { $this->handleSystemSignal($signal); });
            pcntl_signal(SIGINT, function($signal) { $this->handleSystemSignal($signal); });
            pcntl_signal(SIGHUP, function($signal) { $this->handleSystemSignal($signal); });
        }
    }

    /**
     * Handle system signals
     *
     * @param int $signal
     * @return void
     */
    public function handleSystemSignal(int $signal): void
    {
        $signalNames = [
            SIGTERM => 'SIGTERM',
            SIGINT => 'SIGINT',
            SIGHUP => 'SIGHUP'
        ];

        $signalName = $signalNames[$signal] ?? 'UNKNOWN';
        $this->info("Received signal: {$signalName}");

        switch ($signal) {
            case SIGTERM:
            case SIGINT:
                $this->info('Shutting down gracefully...');
                if (isset($this->subscriber)) {
                    $this->subscriber->stop();
                }
                break;

            case SIGHUP:
                $this->info('Reloading configuration...');
                // Could implement config reload here
                break;
        }
    }
}
