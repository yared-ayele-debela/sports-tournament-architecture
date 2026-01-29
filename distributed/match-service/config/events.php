<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Event Channels
    |--------------------------------------------------------------------------
    |
    | Channels to subscribe to for incoming events
    |
    */
    'channels' => [
        // Team events for validation
        'sports.team.created',
        'sports.team.updated',
        'sports.team.deleted',
        
        // Tournament events for scheduling control
        'sports.tournament.created',
        'sports.tournament.updated',
        'sports.tournament.status.changed',
        'sports.tournament.deleted',
        
        // Sport and venue events (for future use)
        'sports.sport.created',
        'sports.sport.updated',
        'sports.sport.deleted',
        'sports.venue.created',
        'sports.venue.updated',
        'sports.venue.deleted',
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Handlers
    |--------------------------------------------------------------------------
    |
    | Event handlers for processing incoming events
    |
    */
    'handlers' => [
        // Team event handlers
        'sports.team.created' => [
            \App\Services\Events\Handlers\TeamEventHandler::class,
        ],
        'sports.team.updated' => [
            \App\Services\Events\Handlers\TeamEventHandler::class,
        ],
        'sports.team.deleted' => [
            \App\Services\Events\Handlers\TeamEventHandler::class,
        ],
        
        // Tournament event handlers
        'sports.tournament.created' => [
            \App\Services\Events\Handlers\TournamentEventHandler::class,
        ],
        'sports.tournament.updated' => [
            \App\Services\Events\Handlers\TournamentEventHandler::class,
        ],
        'sports.tournament.status.changed' => [
            \App\Services\Events\Handlers\TournamentEventHandler::class,
        ],
        'sports.tournament.deleted' => [
            \App\Services\Events\Handlers\TournamentEventHandler::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Publishing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for publishing events
    |
    */
    'publishing' => [
        'default_channel' => env('EVENTS_DEFAULT_CHANNEL', 'sports.events'),
        'enabled' => env('EVENTS_ENABLED', true),
        'version' => env('EVENTS_VERSION', '1.0'),
        
        'retry' => [
            'max_attempts' => env('EVENTS_RETRY_MAX_ATTEMPTS', 3),
            'delay_ms' => env('EVENTS_RETRY_DELAY_MS', 100),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Subscription Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for subscribing to events
    |
    */
    'subscription' => [
        'enabled' => env('EVENTS_SUBSCRIPTION_ENABLED', true),
        'reconnect_delay_ms' => env('EVENTS_RECONNECT_DELAY_MS', 5000),
        'max_reconnect_attempts' => env('EVENTS_MAX_RECONNECT_ATTEMPTS', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Event History
    |--------------------------------------------------------------------------
    |
    | Configuration for storing event history
    |
    */
    'history' => [
        'enabled' => env('EVENTS_HISTORY_ENABLED', false),
        'ttl_seconds' => env('EVENTS_HISTORY_TTL', 86400), // 24 hours
        'max_events' => env('EVENTS_HISTORY_MAX', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Validation
    |--------------------------------------------------------------------------
    |
    | Validation rules for incoming events
    |
    */
    'validation' => [
        'required_fields' => [
            'event_id',
            'event_type',
            'service',
            'payload',
            'timestamp',
            'version',
        ],
        'event_id_format' => 'uuid',
        'timestamp_format' => 'iso8601',
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Configuration
    |--------------------------------------------------------------------------
    |
    | Service-specific configuration
    |
    */
    'service' => [
        'name' => env('EVENTS_SERVICE_NAME', 'match-service'),
        'event_prefix' => 'sports',
        'version' => '1.0',
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Performance-related settings
    |
    */
    'performance' => [
        'batch_size' => env('EVENTS_BATCH_SIZE', 100),
        'worker_timeout' => env('EVENTS_WORKER_TIMEOUT', 300), // 5 minutes
        'memory_limit' => env('EVENTS_MEMORY_LIMIT', '256M'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring & Logging
    |--------------------------------------------------------------------------
    |
    | Monitoring and logging configuration
    |
    */
    'monitoring' => [
        'log_events' => env('EVENTS_LOG_EVENTS', true),
        'log_payloads' => env('EVENTS_LOG_PAYLOADS', false),
        'metrics_enabled' => env('EVENTS_METRICS_ENABLED', false),
        'health_check_interval' => env('EVENTS_HEALTH_CHECK_INTERVAL', 60), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related settings
    |
    */
    'security' => [
        'allowed_sources' => env('EVENTS_ALLOWED_SOURCES', 'tournament-service,team-service,auth-service,results-service,gateway-service'),
        'max_payload_size' => env('EVENTS_MAX_PAYLOAD_SIZE', 1048576), // 1MB
        'sanitize_payloads' => env('EVENTS_SANITIZE_PAYLOADS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Handling
    |--------------------------------------------------------------------------
    |
    | Error handling configuration
    |
    */
    'error_handling' => [
        'max_retry_attempts' => env('EVENTS_ERROR_MAX_RETRIES', 3),
        'retry_delay_ms' => env('EVENTS_ERROR_RETRY_DELAY', 1000),
        'dead_letter_queue' => env('EVENTS_DEAD_LETTER_QUEUE', 'events.dlq'),
        'alert_on_failures' => env('EVENTS_ALERT_ON_FAILURES', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Match Service Specific Settings
    |--------------------------------------------------------------------------
    |
    | Configuration specific to match service event handling
    |
    */
    'match_service' => [
        'cache_team_data' => env('EVENTS_CACHE_TEAM_DATA', true),
        'cache_tournament_data' => env('EVENTS_CACHE_TOURNAMENT_DATA', true),
        'auto_cancel_matches_on_tournament_completion' => env('EVENTS_AUTO_CANCEL_MATCHES', true),
        'validate_teams_before_scheduling' => env('EVENTS_VALIDATE_TEAMS', true),
        'block_scheduling_in_completed_tournaments' => env('EVENTS_BLOCK_COMPLETED_SCHEDULING', true),
    ],
];
