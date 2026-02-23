<?php

namespace App\Contracts;

use Illuminate\Support\Facades\Log;

/**
 * Abstract Base Event Handler
 * 
 * Provides common functionality for event handlers
 */
abstract class BaseEventHandler implements EventHandler
{
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

        return true;
    }

    /**
     * Log event handling
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return void
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        $logMethod = $level === 'error' ? 'error' : 
                   ($level === 'warning' ? 'warning' : 'info');
        
        Log::$logMethod($message, $context);
    }

    /**
     * Handle event with error catching
     *
     * @param array $event
     * @return void
     */
    public function handle(array $event): void
    {
        try {
            if (!$this->validateEvent($event)) {
                $this->log('warning', 'Invalid event structure received', [
                    'event_id' => $event['event_id'] ?? 'unknown',
                    'event_type' => $event['event_type'] ?? 'unknown'
                ]);
                return;
            }

            $this->log('info', 'Processing event', [
                'event_id' => $event['event_id'],
                'event_type' => $event['event_type'],
                'source_service' => $event['service'] ?? 'unknown'
            ]);

            $this->processEvent($event);

            $this->log('info', 'Event processed successfully', [
                'event_id' => $event['event_id'],
                'event_type' => $event['event_type']
            ]);

        } catch (\Exception $e) {
            $this->log('error', 'Failed to process event', [
                'event_id' => $event['event_id'] ?? 'unknown',
                'event_type' => $event['event_type'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Process the event - to be implemented by concrete handlers
     *
     * @param array $event
     * @return void
     */
    abstract protected function processEvent(array $event): void;

    /**
     * Check if this handler can handle the event type
     *
     * @param string $eventType
     * @return bool
     */
    public function canHandle(string $eventType): bool
    {
        return in_array($eventType, $this->getHandledEventTypes());
    }

    /**
     * Get the event types this handler can handle
     *
     * @return array
     */
    abstract public function getHandledEventTypes(): array;
}
