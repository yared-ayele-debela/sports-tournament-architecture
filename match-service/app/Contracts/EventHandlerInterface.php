<?php

namespace App\Contracts;

/**
 * Event Handler Interface
 * 
 * All event handlers must implement this interface.
 * This interface defines the contract for processing queued events.
 */
interface EventHandlerInterface
{
    /**
     * Handle the event
     *
     * @param array $event Event data structure
     * @return void
     */
    public function handle(array $event): void;

    /**
     * Check if handler can handle the event type
     *
     * @param string $eventType Event type identifier (e.g., "match.completed")
     * @return bool True if handler can process this event type
     */
    public function canHandle(string $eventType): bool;

    /**
     * Get list of event types this handler handles
     *
     * @return array Array of event type strings
     */
    public function getHandledEventTypes(): array;
}
