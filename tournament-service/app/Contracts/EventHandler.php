<?php

namespace App\Contracts;

use Illuminate\Support\Facades\Log;

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
     * Check if handler can handle the event type
     *
     * @param string $eventType
     * @return bool
     */
    public function canHandle(string $eventType): bool;

    /**
     * Get list of event types this handler handles
     *
     * @return array
     */
    public function getHandledEventTypes(): array;
}

