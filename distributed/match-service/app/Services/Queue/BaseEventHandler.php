<?php

namespace App\Services\Queue;

use App\Contracts\EventHandlerInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use Exception;

/**
 * Base Event Handler for Queue-based Event Processing
 * 
 * Provides common functionality for all queue event handlers:
 * - Event validation
 * - Logging
 * - Error handling wrapper
 * - Idempotency check (prevents duplicate processing)
 */
abstract class BaseEventHandler implements EventHandlerInterface
{
    /**
     * Service name
     */
    protected string $serviceName;

    /**
     * Idempotency TTL in seconds (default: 30 days)
     */
    protected int $idempotencyTtl = 2592000; // 30 days

    /**
     * Processing lock TTL in seconds (default: 5 minutes)
     */
    protected int $processingLockTtl = 300; // 5 minutes

    /**
     * Initialize the handler
     */
    public function __construct()
    {
        $this->serviceName = config('app.name', 'unknown-service');
    }

    /**
     * Handle the event with error handling wrapper
     *
     * @param array $event Event data structure
     * @return void
     */
    public function handle(array $event): void
    {
        $eventId = $event['event_id'] ?? 'unknown';
        $eventType = $event['event_type'] ?? 'unknown';

        try {
            // Validate event before processing
            if (!$this->validateEvent($event)) {
                $this->warningLog('Invalid event received', $event);
                return;
            }

            // Check if we should handle this event
            if (!$this->canHandle($eventType)) {
                $this->debugLog('Event type not handled by this handler', $event);
                return;
            }

            // IDEMPOTENCY CHECK: Prevent duplicate processing
            if ($this->isEventProcessed($eventId)) {
                $this->warningLog('Event already processed, skipping (idempotency)', $event, [
                    'event_id' => $eventId,
                ]);
                return;
            }

            // Check if event is currently being processed (prevent concurrent processing)
            if ($this->isEventProcessing($eventId)) {
                $this->warningLog('Event is currently being processed, skipping', $event, [
                    'event_id' => $eventId,
                ]);
                return;
            }

            // Mark event as processing
            $this->markEventProcessing($eventId);

            // Log event processing start
            $this->infoLog('Processing event', $event);

            // Call the concrete implementation
            $this->processEvent($event);

            // Mark event as processed (idempotency)
            $this->markEventProcessed($eventId);

            // Log successful processing
            $this->infoLog('Event processed successfully', $event);

        } catch (Exception $e) {
            $this->errorLog('Event handling failed', $event, [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            // Clear processing lock on error
            $this->clearProcessingLock($eventId);

            // Re-throw to allow job retry mechanism
            throw $e;
        }
    }

    /**
     * Process the event - concrete implementation
     * Must be implemented by child classes
     *
     * @param array $event Event data structure
     * @return void
     */
    abstract protected function processEvent(array $event): void;

    /**
     * Check if handler can handle the event type
     *
     * @param string $eventType Event type identifier
     * @return bool
     */
    public function canHandle(string $eventType): bool
    {
        return in_array($eventType, $this->getHandledEventTypes());
    }

    /**
     * Validate event structure
     *
     * @param array $event Event data
     * @return bool
     */
    protected function validateEvent(array $event): bool
    {
        $requiredFields = ['event_id', 'event_type', 'service', 'payload', 'timestamp', 'version'];
        
        foreach ($requiredFields as $field) {
            if (!isset($event[$field])) {
                Log::warning('Missing required event field', [
                    'field' => $field,
                    'event_id' => $event['event_id'] ?? 'unknown',
                    'service' => $this->serviceName,
                ]);
                return false;
            }
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

        return true;
    }

    /**
     * Check if event has already been processed (idempotency)
     *
     * @param string $eventId Event ID
     * @return bool True if event was already processed
     */
    protected function isEventProcessed(string $eventId): bool
    {
        $key = "events:processed:{$eventId}";

        try {
            return Redis::exists($key) > 0;
        } catch (Exception $e) {
            Log::warning('Failed to check event processing status in Redis', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
                'service' => $this->serviceName,
            ]);

            // Fallback to database check if Redis fails
            return $this->isEventProcessedInDatabase($eventId);
        }
    }

    /**
     * Check if event is currently being processed (prevents concurrent processing)
     *
     * @param string $eventId Event ID
     * @return bool True if event is currently being processed
     */
    protected function isEventProcessing(string $eventId): bool
    {
        $key = "events:processing:{$eventId}";

        try {
            return Redis::exists($key) > 0;
        } catch (Exception $e) {
            Log::warning('Failed to check event processing lock in Redis', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
                'service' => $this->serviceName,
            ]);

            // On Redis failure, assume not processing (allow processing)
            return false;
        }
    }

    /**
     * Mark event as currently processing
     *
     * @param string $eventId Event ID
     * @return void
     */
    protected function markEventProcessing(string $eventId): void
    {
        $key = "events:processing:{$eventId}";

        try {
            Redis::setex($key, $this->processingLockTtl, Carbon::now()->toIso8601String());
        } catch (Exception $e) {
            Log::warning('Failed to mark event as processing in Redis', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
                'service' => $this->serviceName,
            ]);
        }
    }

    /**
     * Mark event as processed (idempotency)
     *
     * @param string $eventId Event ID
     * @param array|null $metadata Optional metadata to store
     * @return void
     */
    protected function markEventProcessed(string $eventId, ?array $metadata = null): void
    {
        $key = "events:processed:{$eventId}";
        $data = [
            'event_id' => $eventId,
            'processed_at' => Carbon::now()->toIso8601String(),
            'service' => $this->serviceName,
            'handler' => static::class,
        ];

        if ($metadata !== null) {
            $data['metadata'] = $metadata;
        }

        try {
            // Store for idempotency TTL (default: 30 days)
            Redis::setex($key, $this->idempotencyTtl, json_encode($data));
        } catch (Exception $e) {
            Log::warning('Failed to mark event as processed in Redis', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
                'service' => $this->serviceName,
            ]);

            // Fallback to database storage
            $this->markEventProcessedInDatabase($eventId, $data);
        }

        // Clear processing lock
        $this->clearProcessingLock($eventId);
    }

    /**
     * Clear processing lock
     *
     * @param string $eventId Event ID
     * @return void
     */
    protected function clearProcessingLock(string $eventId): void
    {
        $key = "events:processing:{$eventId}";

        try {
            Redis::del($key);
        } catch (Exception $e) {
            Log::warning('Failed to clear processing lock in Redis', [
                'event_id' => $eventId,
                'error' => $e->getMessage(),
                'service' => $this->serviceName,
            ]);
        }
    }

    /**
     * Check if event processed in database (fallback method)
     * Override this method in child classes to implement database-based idempotency
     *
     * @param string $eventId Event ID
     * @return bool
     */
    protected function isEventProcessedInDatabase(string $eventId): bool
    {
        // Override in child classes if database-based idempotency is needed
        return false;
    }

    /**
     * Mark event as processed in database (fallback method)
     * Override this method in child classes to implement database-based idempotency
     *
     * @param string $eventId Event ID
     * @param array $data Event processing data
     * @return void
     */
    protected function markEventProcessedInDatabase(string $eventId, array $data): void
    {
        // Override in child classes if database-based idempotency is needed
        // Example: Store in a processed_events table
    }

    /**
     * Extract data from event payload with default
     *
     * @param array $event Event data
     * @param string $key Payload key
     * @param mixed $default Default value
     * @return mixed
     */
    protected function getPayloadData(array $event, string $key, $default = null)
    {
        return $event['payload'][$key] ?? $default;
    }

    /**
     * Check if event is from the same service (to avoid loops)
     *
     * @param array $event Event data
     * @return bool
     */
    protected function isFromSameService(array $event): bool
    {
        return ($event['service'] ?? '') === $this->serviceName;
    }

    /**
     * Log debug message with context
     *
     * @param string $message Log message
     * @param array $event Event data
     * @param array $context Additional context
     * @return void
     */
    protected function debugLog(string $message, array $event, array $context = []): void
    {
        Log::debug($message, array_merge([
            'service' => $this->serviceName,
            'handler' => static::class,
            'event_id' => $event['event_id'] ?? 'unknown',
            'event_type' => $event['event_type'] ?? 'unknown',
        ], $context));
    }

    /**
     * Log info message with context
     *
     * @param string $message Log message
     * @param array $event Event data
     * @param array $context Additional context
     * @return void
     */
    protected function infoLog(string $message, array $event, array $context = []): void
    {
        Log::info($message, array_merge([
            'service' => $this->serviceName,
            'handler' => static::class,
            'event_id' => $event['event_id'] ?? 'unknown',
            'event_type' => $event['event_type'] ?? 'unknown',
        ], $context));
    }

    /**
     * Log warning message with context
     *
     * @param string $message Log message
     * @param array $event Event data
     * @param array $context Additional context
     * @return void
     */
    protected function warningLog(string $message, array $event, array $context = []): void
    {
        Log::warning($message, array_merge([
            'service' => $this->serviceName,
            'handler' => static::class,
            'event_id' => $event['event_id'] ?? 'unknown',
            'event_type' => $event['event_type'] ?? 'unknown',
        ], $context));
    }

    /**
     * Log error message with context
     *
     * @param string $message Log message
     * @param array $event Event data
     * @param array $context Additional context
     * @return void
     */
    protected function errorLog(string $message, array $event, array $context = []): void
    {
        Log::error($message, array_merge([
            'service' => $this->serviceName,
            'handler' => static::class,
            'event_id' => $event['event_id'] ?? 'unknown',
            'event_type' => $event['event_type'] ?? 'unknown',
        ], $context));
    }

    /**
     * Get service name
     *
     * @return string
     */
    protected function getServiceName(): string
    {
        return $this->serviceName;
    }

    /**
     * Set idempotency TTL
     *
     * @param int $seconds TTL in seconds
     * @return self
     */
    public function setIdempotencyTtl(int $seconds): self
    {
        $this->idempotencyTtl = $seconds;
        return $this;
    }

    /**
     * Set processing lock TTL
     *
     * @param int $seconds TTL in seconds
     * @return self
     */
    public function setProcessingLockTtl(int $seconds): self
    {
        $this->processingLockTtl = $seconds;
        return $this;
    }
}
