<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Events\EventSubscriber;
use App\Contracts\EventHandler;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Artisan Command to Run Event Listeners
 * 
 * Usage: php artisan events:listen [--channels=channel1,channel2]
 */
class EventsListenCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'events:listen {--channels= : Comma-separated list of channels to subscribe to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start event listener daemon for Redis Pub/Sub channels';

    protected EventSubscriber $subscriber;
    protected array $handlers = [];

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('Starting Event Listener Daemon...');
        
        try {
            // Register signal handlers for graceful shutdown
            $this->registerSignalHandlers();
            
            // Load configuration
            $channels = $this->getChannels();
            $this->handlers = $this->loadHandlers();

            if (empty($channels)) {
                $this->error('No channels configured. Please set up channels in config/events.php or use --channels parameter.');
                return 1;
            }

            if (empty($this->handlers)) {
                $this->error('No event handlers configured. Please set up handlers in config/events.php.');
                return 1;
            }

            $this->info('Loaded ' . count($this->handlers) . ' event handlers');
            $this->info('Subscribing to channels: ' . implode(', ', $channels));

            // Create and configure subscriber
            $this->subscriber = new EventSubscriber();
            $this->subscriber->setServiceName(config('app.name', 'unknown-service'));

            // Start listening
            $this->subscriber->subscribe($channels, [$this, 'handleEvent']);

            $this->info('Event listener stopped gracefully.');
            return 0;

        } catch (Exception $e) {
            $this->error('Event listener failed: ' . $e->getMessage());
            Log::error('Event listener command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Get channels to subscribe to
     *
     * @return array
     */
    protected function getChannels(): array
    {
        // Check command line option first
        $channelsOption = $this->option('channels');
        if ($channelsOption) {
            return array_map('trim', explode(',', $channelsOption));
        }

        // Fall back to config
        return config('events.channels', []);
    }

    /**
     * Load event handlers from configuration
     *
     * @return array
     */
    protected function loadHandlers(): array
    {
        $handlerClasses = config('events.handlers', []);
        $handlers = [];

        foreach ($handlerClasses as $handlerClass) {
            try {
                if (!class_exists($handlerClass)) {
                    $this->warn("Handler class not found: {$handlerClass}");
                    continue;
                }

                $handler = app($handlerClass);
                
                if (!$handler instanceof EventHandler) {
                    $this->warn("Handler class does not implement EventHandler interface: {$handlerClass}");
                    continue;
                }

                $handlers[] = $handler;
                
                $this->info("Loaded handler: {$handlerClass} (handles: " . implode(', ', $handler->getHandledEventTypes()) . ")");
                
            } catch (Exception $e) {
                $this->error("Failed to load handler {$handlerClass}: " . $e->getMessage());
                Log::error('Failed to load event handler', [
                    'handler_class' => $handlerClass,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $handlers;
    }

    /**
     * Handle incoming events
     *
     * @param array $event
     * @param string $channel
     * @return void
     */
    public function handleEvent(array $event, string $channel): void
    {
        $handled = false;

        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($event['event_type'])) {
                try {
                    $handler->handle($event);
                    $handled = true;
                } catch (Exception $e) {
                    $this->error("Handler failed for event {$event['event_id']}: " . $e->getMessage());
                    Log::error('Event handler failed', [
                        'event_id' => $event['event_id'],
                        'event_type' => $event['event_type'],
                        'handler_class' => get_class($handler),
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        if (!$handled) {
            $this->warn("No handler found for event type: {$event['event_type']}");
            Log::debug('No handler found for event', [
                'event_id' => $event['event_id'],
                'event_type' => $event['event_type'],
                'channel' => $channel,
                'available_handlers' => array_map(function($h) { 
                    return get_class($h) . ': ' . implode(', ', $h->getHandledEventTypes()); 
                }, $this->handlers)
            ]);
        }
    }

    /**
     * Handle graceful shutdown
     *
     * @return void
     */
    public function __destruct()
    {
        if (isset($this->subscriber) && $this->subscriber->isRunning()) {
            $this->info('Stopping event subscriber...');
            $this->subscriber->stop();
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
