<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Event Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for event publishing and handling in the Tournament Service.
    |
    */

    /**
     * Redis channel name for publishing events.
     */
    'channel' => env('EVENTS_CHANNEL', 'sports-tournament-events'),

    /**
     * Event TTL in seconds for history storage.
     */
    'history_ttl' => env('EVENTS_HISTORY_TTL', 86400), // 24 hours

    /**
     * Maximum number of events to keep in history.
     */
    'max_history_events' => env('EVENTS_MAX_HISTORY', 1000),

    /**
     * Enable/disable event publishing.
     */
    'enabled' => env('EVENTS_ENABLED', true),

    /**
     * Event versioning.
     */
    'version' => '1.0',

    /**
     * Event retry configuration.
     */
    'retry' => [
        'max_attempts' => env('EVENTS_RETRY_MAX_ATTEMPTS', 3),
        'delay_ms' => env('EVENTS_RETRY_DELAY_MS', 100),
    ],
];
