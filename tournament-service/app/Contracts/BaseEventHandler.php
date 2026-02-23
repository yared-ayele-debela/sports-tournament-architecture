<?php

namespace App\Contracts;

use Illuminate\Support\Facades\Log;

/**
 * Abstract Base Event Handler
 *
 * Provides common functionality for all event handlers
 */
abstract class BaseEventHandler implements EventHandler
{
    protected string $serviceName;

    public function __construct()
    {
        $this->serviceName = config('app.name', 'unknown-service');
    }

    /**
     * Handle the event with error handling wrapper
     *
     * @param array $event
     * @return void
     */
    public function handle(array $event): void
    {
        try {
            // Validate event before processing
            if (!$this->validateEvent($event)) {
                Log::warning('Invalid event received', [
                    'service' => $this->serviceName,
                    'handler' => static::class,
                    'event_id' => $event['event_id'] ?? 'unknown',
                    'event_type' => $event['event_type'] ?? 'unknown'
                ]);
                return;
            }

            // Check if we should handle this event
            if (!$this->canHandle($event['event_type'])) {
                Log::debug('Event type not handled', [
                    'service' => $this->serviceName,
                    'handler' => static::class,
                    'event_id' => $event['event_id'] ?? 'unknown',
                    'event_type' => $event['event_type'] ?? 'unknown',
                    'handled_types' => $this->getHandledEventTypes()
                ]);
                return;
            }

            // Log event processing start
            Log::info('Processing event', [
                'service' => $this->serviceName,
                'handler' => static::class,
                'event_id' => $event['event_id'],
                'event_type' => $event['event_type'],
                'source_service' => $event['service'] ?? 'unknown'
            ]);

            // Call the concrete implementation
            $this->processEvent($event);

            // Log successful processing
            Log::info('Event processed successfully', [
                'service' => $this->serviceName,
                'handler' => static::class,
                'event_id' => $event['event_id'],
                'event_type' => $event['event_type']
            ]);

        } catch (\Exception $e) {
            Log::error('Event handling failed', [
                'service' => $this->serviceName,
                'handler' => static::class,
                'event_id' => $event['event_id'] ?? 'unknown',
                'event_type' => $event['event_type'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Process the event - concrete implementation
     *
     * @param array $event
     * @return void
     */
    abstract protected function processEvent(array $event): void;

    /**
     * Check if handler can handle the event type
     *
     * @param string $eventType
     * @return bool
     */
    public function canHandle(string $eventType): bool
    {
        return in_array($eventType, $this->getHandledEventTypes());
    }

    /**
     * Validate event structure
     *
     * @param array $event
     * @return bool
     */
    protected function validateEvent(array $event): bool
    {
        $requiredFields = ['event_id', 'event_type', 'service', 'payload', 'timestamp', 'version'];

        foreach ($requiredFields as $field) {
            if (!isset($event[$field])) {
                return false;
            }
        }

        // Validate UUID format for event_id
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $event['event_id'])) {
            return false;
        }

        // Validate timestamp format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $event['timestamp'])) {
            return false;
        }

        return true;
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
     * Set service name (useful for testing)
     *
     * @param string $serviceName
     * @return void
     */
    public function setServiceName(string $serviceName): void
    {
        $this->serviceName = $serviceName;
    }

    /**
     * Extract data from event payload with default
     *
     * @param array $event
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getPayloadData(array $event, string $key, $default = null)
    {
        return $event['payload'][$key] ?? $default;
    }

    /**
     * Check if event is from the same service (to avoid loops)
     *
     * @param array $event
     * @return bool
     */
    protected function isFromSameService(array $event): bool
    {
        return ($event['service'] ?? '') === $this->serviceName;
    }

    /**
     * Log debug message with context
     *
     * @param string $message
     * @param array $event
     * @param array $context
     * @return void
     */
    protected function debugLog(string $message, array $event, array $context = []): void
    {
        Log::debug($message, array_merge([
            'service' => $this->serviceName,
            'handler' => static::class,
            'event_id' => $event['event_id'] ?? 'unknown',
            'event_type' => $event['event_type'] ?? 'unknown'
        ], $context));
    }

    /**
     * Log info message with context
     *
     * @param string $message
     * @param array $event
     * @param array $context
     * @return void
     */
    protected function infoLog(string $message, array $event, array $context = []): void
    {
        Log::info($message, array_merge([
            'service' => $this->serviceName,
            'handler' => static::class,
            'event_id' => $event['event_id'] ?? 'unknown',
            'event_type' => $event['event_type'] ?? 'unknown'
        ], $context));
    }

    /**
     * Log warning message with context
     *
     * @param string $message
     * @param array $event
     * @param array $context
     * @return void
     */
    protected function warningLog(string $message, array $event, array $context = []): void
    {
        Log::warning($message, array_merge([
            'service' => $this->serviceName,
            'handler' => static::class,
            'event_id' => $event['event_id'] ?? 'unknown',
            'event_type' => $event['event_type'] ?? 'unknown'
        ], $context));
    }

    /**
     * Log error message with context
     *
     * @param string $message
     * @param array $event
     * @param array $context
     * @return void
     */
    protected function errorLog(string $message, array $event, array $context = []): void
    {
        Log::error($message, array_merge([
            'service' => $this->serviceName,
            'handler' => static::class,
            'event_id' => $event['event_id'] ?? 'unknown',
            'event_type' => $event['event_type'] ?? 'unknown'
        ], $context));
    }
}
