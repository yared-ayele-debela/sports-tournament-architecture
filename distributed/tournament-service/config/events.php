<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Event Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for event publishing and handling across all services.
    | This file defines channels, handlers, and event routing rules.
    |
    */

    /**
     * Channels to subscribe to for event listening.
     * Each service should subscribe to relevant channels.
     */
    'channels' => [
        // Tournament service events
        'sports.tournament.created',
        'sports.tournament.updated',
        'sports.tournament.deleted',
        'sports.tournament.started',
        'sports.tournament.completed',
        'sports.tournament.status.changed',
        
        // Match service events
        'sports.match.created',
        'sports.match.updated',
        'sports.match.completed',
        'sports.match.cancelled',
        'sports.match.score.updated',
        'sports.match.event.added',
        
        // Team service events
        'sports.team.created',
        'sports.team.updated',
        'sports.team.deleted',
        'sports.team.player.added',
        'sports.team.player.removed',
        'sports.team.player.updated',
        
        // Results service events
        'sports.results.finalized',
        'sports.results.standings.updated',
        'sports.results.statistics.updated',
        
        // Auth service events
        'sports.auth.user.created',
        'sports.auth.user.updated',
        'sports.auth.user.deleted',
        'sports.auth.token.revoked',
        
        // Gateway service events (if needed)
        'sports.gateway.request.completed',
        'sports.gateway.error.occurred',
    ],

    /**
     * Event handler classes to load for event processing.
     * Each handler must implement the EventHandler interface.
     */
    'handlers' => [
        // Add your event handler classes here
        // Example:
        // 'App\\Events\\Handlers\\TournamentEventHandler',
        // 'App\\Events\\Handlers\\MatchEventHandler',
        // 'App\\Events\\Handlers\\TeamEventHandler',
        // 'App\\Events\\Handlers\\ResultsEventHandler',
    ],

    /**
     * Event publishing configuration.
     */
    'publishing' => [
        /**
         * Default channel for publishing events.
         */
        'default_channel' => env('EVENTS_DEFAULT_CHANNEL', 'sports.events'),
        
        /**
         * Enable/disable event publishing.
         */
        'enabled' => env('EVENTS_ENABLED', true),
        
        /**
         * Event versioning.
         */
        'version' => env('EVENTS_VERSION', '1.0'),
        
        /**
         * Event retry configuration.
         */
        'retry' => [
            'max_attempts' => env('EVENTS_RETRY_MAX_ATTEMPTS', 3),
            'delay_ms' => env('EVENTS_RETRY_DELAY_MS', 100),
        ],
    ],

    /**
     * Event subscription configuration.
     */
    'subscription' => [
        /**
         * Reconnection delay in milliseconds.
         */
        'reconnect_delay_ms' => env('EVENTS_RECONNECT_DELAY_MS', 5000),
        
        /**
         * Maximum reconnection attempts.
         */
        'max_reconnect_attempts' => env('EVENTS_MAX_RECONNECT_ATTEMPTS', 10),
        
        /**
         * Enable/disable event subscription.
         */
        'enabled' => env('EVENTS_SUBSCRIPTION_ENABLED', true),
    ],

    /**
     * Event history and logging configuration.
     */
    'history' => [
        /**
         * Event TTL in seconds for history storage.
         */
        'ttl' => env('EVENTS_HISTORY_TTL', 86400), // 24 hours
        
        /**
         * Maximum number of events to keep in history.
         */
        'max_events' => env('EVENTS_MAX_HISTORY', 1000),
        
        /**
         * Enable/disable event history storage.
         */
        'enabled' => env('EVENTS_HISTORY_ENABLED', true),
    ],

    /**
     * Event validation configuration.
     */
    'validation' => [
        /**
         * Enable strict event validation.
         */
        'strict' => env('EVENTS_VALIDATION_STRICT', true),
        
        /**
         * Required event fields.
         */
        'required_fields' => [
            'event_id',
            'event_type',
            'service',
            'payload',
            'timestamp',
            'version',
        ],
    ],

    /**
     * Service-specific configuration.
     */
    'service' => [
        /**
         * Service name for event identification.
         */
        'name' => env('EVENTS_SERVICE_NAME', config('app.name', 'unknown-service')),
        
        /**
         * Service-specific event prefixes.
         */
        'event_prefix' => env('EVENTS_SERVICE_PREFIX', 'sports'),
    ],
];
