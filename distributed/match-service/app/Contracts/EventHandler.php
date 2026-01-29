<?php

namespace App\Contracts;

/**
 * Event Handler Interface
 * 
 * All event handlers must implement this interface
 */
interface EventHandler
{
    /**
     * Handle the event
     *
     * @param array $event
     * @return void
     */
    public function handle(array $event): void;

    /**
     * Check if this handler can handle the event type
     *
     * @param string $eventType
     * @return bool
     */
    public function canHandle(string $eventType): bool;

    /**
     * Get the event types this handler can handle
     *
     * @return array
     */
    public function getHandledEventTypes(): array;
}
