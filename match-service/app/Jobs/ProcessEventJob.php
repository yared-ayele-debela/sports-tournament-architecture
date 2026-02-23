<?php

namespace App\Jobs;

use App\Contracts\EventHandlerInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use Exception;
use InvalidArgumentException;

class ProcessEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array
     */
    public $backoff = [10, 30, 60];

    /**
     * The event data to process
     *
     * @var array
     */
    public array $event;

    /**
     * Service name for logging
     *
     * @var string
     */
    protected string $serviceName;

    /**
     * Create a new job instance.
     *
     * @param array $event Event data structure
     * @throws InvalidArgumentException If event structure is invalid
     */
    public function __construct(array $event)
    {
        $this->event = $event;
        $this->serviceName = config('app.name', 'unknown-service');

        // Validate event structure on construction
        if (!$this->validateEventStructure($event)) {
            throw new InvalidArgumentException('Invalid event structure provided to ProcessEventJob');
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception If event processing fails
     */
    public function handle(): void
    {
        $eventId = $this->event['event_id'] ?? 'unknown';
        $eventType = $this->event['event_type'] ?? 'unknown';
        $attempt = $this->attempts();

        // Update retry count in event data
        $this->event['retry_count'] = $attempt - 1;

        Log::info('Processing queued event', [
            'event_id' => $eventId,
            'event_type' => $eventType,
            'service' => $this->serviceName,
            'source_service' => $this->event['service'] ?? 'unknown',
            'attempt' => $attempt,
            'max_attempts' => $this->tries,
            'timestamp' => $this->event['timestamp'] ?? null,
        ]);

        try {
            // Validate event before processing
            if (!$this->validateEvent($this->event)) {
                throw new InvalidArgumentException('Event validation failed');
            }

            // Route to appropriate handler
            $handled = $this->routeToHandler($this->event);

            if (!$handled) {
                Log::warning('No handler found for event type', [
                    'event_id' => $eventId,
                    'event_type' => $eventType,
                    'service' => $this->serviceName,
                    'available_handlers' => $this->getAvailableHandlerTypes(),
                ]);

                // Don't throw exception for unhandled events - just log and complete
                // This prevents unnecessary retries for events that will never be handled
                return;
            }

            Log::info('Event processed successfully', [
                'event_id' => $eventId,
                'event_type' => $eventType,
                'service' => $this->serviceName,
                'attempt' => $attempt,
            ]);

        } catch (InvalidArgumentException $e) {
            // Validation errors should not be retried
            Log::error('Event validation error (not retrying)', [
                'event_id' => $eventId,
                'event_type' => $eventType,
                'service' => $this->serviceName,
                'error' => $e->getMessage(),
                'attempt' => $attempt,
            ]);

            // Don't re-throw validation errors - they won't be fixed by retrying
            return;

        } catch (Exception $e) {
            Log::error('Error processing event (will retry)', [
                'event_id' => $eventId,
                'event_type' => $eventType,
                'service' => $this->serviceName,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'attempt' => $attempt,
                'max_attempts' => $this->tries,
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Route event to appropriate handler based on event_type
     *
     * @param array $event Event data
     * @return bool True if event was handled, false otherwise
     */
    protected function routeToHandler(array $event): bool
    {
        $eventType = $event['event_type'] ?? '';
        $handlers = $this->loadHandlers();

        if (empty($handlers)) {
            Log::warning('No event handlers configured', [
                'event_id' => $event['event_id'] ?? 'unknown',
                'event_type' => $eventType,
                'service' => $this->serviceName,
            ]);
            return false;
        }

        $handled = false;

        foreach ($handlers as $handler) {
            if ($handler->canHandle($eventType)) {
                try {
                    $handler->handle($event);
                    $handled = true;

                    Log::debug('Event handled by handler', [
                        'event_id' => $event['event_id'] ?? 'unknown',
                        'event_type' => $eventType,
                        'handler_class' => get_class($handler),
                        'service' => $this->serviceName,
                    ]);

                } catch (Exception $e) {
                    Log::error('Handler failed to process event', [
                        'event_id' => $event['event_id'] ?? 'unknown',
                        'event_type' => $eventType,
                        'handler_class' => get_class($handler),
                        'service' => $this->serviceName,
                        'error' => $e->getMessage(),
                        'error_class' => get_class($e),
                    ]);

                    // Continue to next handler - don't fail the job if one handler fails
                    // unless it's the only handler
                    if (count($handlers) === 1) {
                        throw $e; // Re-throw if it's the only handler
                    }
                }
            }
        }

        return $handled;
    }

    /**
     * Load event handlers from configuration
     *
     * @return array Array of EventHandler instances
     */
    protected function loadHandlers(): array
    {
        $handlerMap = config('events.handlers', []);
        $handlers = [];

        // Load handlers from config - can be array of classes or event_type => class mapping
        if (isset($handlerMap[$this->event['event_type'] ?? ''])) {
            // Event-specific handler mapping
            $handlerClasses = is_array($handlerMap[$this->event['event_type']]) 
                ? $handlerMap[$this->event['event_type']] 
                : [$handlerMap[$this->event['event_type']]];
        } else {
            // Load all handlers from config
            $handlerClasses = is_array($handlerMap) && !isset($handlerMap[0]) 
                ? array_values($handlerMap) 
                : (array) $handlerMap;
        }

        foreach ($handlerClasses as $handlerClass) {
            try {
                if (!class_exists($handlerClass)) {
                    Log::warning('Handler class not found', [
                        'handler_class' => $handlerClass,
                        'service' => $this->serviceName,
                    ]);
                    continue;
                }

                $handler = App::make($handlerClass);

                if (!$handler instanceof EventHandlerInterface) {
                    Log::warning('Handler class does not implement EventHandler interface', [
                        'handler_class' => $handlerClass,
                        'service' => $this->serviceName,
                    ]);
                    continue;
                }

                $handlers[] = $handler;

            } catch (Exception $e) {
                Log::error('Failed to load event handler', [
                    'handler_class' => $handlerClass,
                    'service' => $this->serviceName,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $handlers;
    }

    /**
     * Get list of available handler types for logging
     *
     * @return array
     */
    protected function getAvailableHandlerTypes(): array
    {
        $handlers = $this->loadHandlers();
        $types = [];

        foreach ($handlers as $handler) {
            $types[get_class($handler)] = $handler->getHandledEventTypes();
        }

        return $types;
    }

    /**
     * Validate event structure (basic structure check)
     *
     * @param array $event Event data
     * @return bool
     */
    protected function validateEventStructure(array $event): bool
    {
        $requiredFields = ['event_id', 'event_type', 'service', 'payload', 'timestamp', 'version'];

        foreach ($requiredFields as $field) {
            if (!isset($event[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate event (full validation including format checks)
     *
     * @param array $event Event data
     * @return bool
     */
    protected function validateEvent(array $event): bool
    {
        // Check required fields
        if (!$this->validateEventStructure($event)) {
            return false;
        }

        // Validate UUID format for event_id
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $event['event_id'])) {
            Log::warning('Invalid event_id format', [
                'event_id' => $event['event_id'],
                'service' => $this->serviceName,
            ]);
            return false;
        }

        // Validate timestamp format (ISO 8601) - supports multiple formats
        // Formats: 2026-01-31T16:43:18Z, 2026-01-31T16:43:18+00:00, 2026-01-31T16:43:18.123Z, etc.
        $timestampPattern = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d{1,6})?(Z|[+-]\d{2}:\d{2})?$/';
        if (!preg_match($timestampPattern, $event['timestamp'])) {
            Log::warning('Invalid timestamp format', [
                'timestamp' => $event['timestamp'],
                'service' => $this->serviceName,
            ]);
            return false;
        }

        // Validate payload is an array
        if (!is_array($event['payload'])) {
            Log::warning('Invalid payload type', [
                'payload_type' => gettype($event['payload']),
                'service' => $this->serviceName,
            ]);
            return false;
        }

        // Validate version format
        if (!is_string($event['version']) || empty($event['version'])) {
            Log::warning('Invalid version format', [
                'version' => $event['version'],
                'service' => $this->serviceName,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Handle a job failure.
     *
     * @param Exception $exception
     * @return void
     */
    public function failed(Exception $exception): void
    {
        $eventId = $this->event['event_id'] ?? 'unknown';
        $eventType = $this->event['event_type'] ?? 'unknown';

        Log::error('ProcessEventJob failed permanently after all retries', [
            'event_id' => $eventId,
            'event_type' => $eventType,
            'service' => $this->serviceName,
            'source_service' => $this->event['service'] ?? 'unknown',
            'error' => $exception->getMessage(),
            'error_class' => get_class($exception),
            'final_attempt' => $this->attempts(),
            'max_attempts' => $this->tries,
            'event_data' => $this->event,
            'trace' => $exception->getTraceAsString(),
        ]);

        // Here you could implement additional failure handling:
        // - Store in dead letter queue
        // - Send notification to administrators
        // - Store in database for manual review
        // - Publish to a failed events channel
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array
     */
    public function backoff(): array
    {
        return $this->backoff;
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil(): \DateTime
    {
        // Retry for up to 1 hour from now
        return now()->addHour();
    }
}
