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
        'sports.tournament.created',
        'sports.tournament.updated',
        'sports.tournament.status.changed',
        'sports.tournament.deleted',
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
    | Event handlers for processing incoming events from queue
    | Maps event types to handler classes
    |
    */
    'handlers' => [
        // Cache invalidation handler for public API cache
        \App\Services\Events\Handlers\CacheInvalidationHandler::class,
        
        // Tournament event handlers
        'tournament.created' => \App\Handlers\TournamentCreatedHandler::class,
        'tournament.status.changed' => \App\Handlers\TournamentStatusChangedHandler::class,
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
        'name' => env('EVENTS_SERVICE_NAME', 'team-service'),
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
        'allowed_sources' => env('EVENTS_ALLOWED_SOURCES', 'tournament-service,auth-service,match-service,results-service,gateway-service'),
        'max_payload_size' => env('EVENTS_MAX_PAYLOAD_SIZE', 1048576), // 1MB
        'sanitize_payloads' => env('EVENTS_SANITIZE_PAYLOADS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Invalidation Mapping
    |--------------------------------------------------------------------------
    |
    | Maps event types to cache keys and tags that should be invalidated.
    | Supports placeholders that are replaced with event payload data.
    |
    | Placeholders format: {payload.field_name} or {payload.nested.field}
    |
    | Available cache strategies:
    | - 'tags': Array of cache tags to invalidate
    | - 'keys': Array of cache key patterns (supports wildcards)
    | - 'patterns': Array of advanced patterns for complex matching
    |
    */
    'cache_invalidation_map' => [
        // Tournament Events (from tournament-service)
        'sports.tournament.created' => [
            'tags' => ['tournaments', 'public:tournaments'],
            'keys' => [
                'tournaments',
                'tournaments:*',
                'public:tournaments',
                'public:tournaments:*'
            ]
        ],
        'sports.tournament.updated' => [
            'tags' => ['tournaments', 'public:tournaments'],
            'keys' => [
                'tournaments:{payload.tournament_id}',
                'tournaments:*',
                'public:tournaments:{payload.tournament_id}',
                'public:tournaments:*'
            ]
        ],
        'sports.tournament.status.changed' => [
            'tags' => ['tournaments', 'public:tournaments'],
            'keys' => [
                'tournaments:{payload.tournament_id}',
                'public:tournaments:{payload.tournament_id}',
                'public:tournaments:*'
            ]
        ],
        'sports.tournament.deleted' => [
            'tags' => ['tournaments', 'public:tournaments'],
            'keys' => [
                'tournaments:*',
                'public:tournaments:*'
            ]
        ],

        // Sport Events (from tournament-service)
        'sports.sport.created' => [
            'tags' => ['sports', 'public:sports'],
            'keys' => [
                'sports',
                'sports:*',
                'public:sports',
                'public:sports:*'
            ]
        ],
        'sports.sport.updated' => [
            'tags' => ['sports', 'public:sports'],
            'keys' => [
                'sports:{payload.sport_id}',
                'sports:*',
                'public:sports:{payload.sport_id}',
                'public:sports:*'
            ]
        ],
        'sports.sport.deleted' => [
            'tags' => ['sports', 'public:sports'],
            'keys' => [
                'sports:*',
                'public:sports:*'
            ]
        ],

        // Venue Events (from tournament-service)
        'sports.venue.created' => [
            'tags' => ['venues', 'public:venues'],
            'keys' => [
                'venues',
                'venues:*',
                'public:venues',
                'public:venues:*'
            ]
        ],
        'sports.venue.updated' => [
            'tags' => ['venues', 'public:venues'],
            'keys' => [
                'venues:{payload.venue_id}',
                'venues:*',
                'public:venues:{payload.venue_id}',
                'public:venues:*'
            ]
        ],
        'sports.venue.deleted' => [
            'tags' => ['venues', 'public:venues'],
            'keys' => [
                'venues:*',
                'public:venues:*'
            ]
        ],

        // Team Events (local to team-service)
        'team.created' => [
            'tags' => ['teams', 'public:tournaments', 'public:teams'],
            'keys' => [
                'teams',
                'teams:*',
                'public:tournaments:{payload.tournament_id}:*',
                'public:teams:{payload.team_id}',
                'public:teams:*'
            ]
        ],
        'team.updated' => [
            'tags' => ['teams', 'public:tournaments', 'public:teams'],
            'keys' => [
                'teams:{payload.team_id}',
                'teams:*',
                'public:tournaments:{payload.tournament_id}:*',
                'public:teams:{payload.team_id}',
                'public:teams:*'
            ]
        ],
        'team.deleted' => [
            'tags' => ['teams', 'public:tournaments', 'public:teams', 'public:players'],
            'keys' => [
                'teams:*',
                'public:tournaments:*',
                'public:teams:*',
                'public:players:*'
            ]
        ],

        // Player Events (local to team-service)
        'player.created' => [
            'tags' => ['players', 'teams', 'public:tournaments', 'public:teams', 'public:players'],
            'keys' => [
                'players:{payload.player_id}',
                'teams:{payload.team_id}:players',
                'public:tournaments:{payload.tournament_id}:*',
                'public:teams:{payload.team_id}',
                'public:players:{payload.team_id}',
                'public:players:{payload.player_id}'
            ]
        ],
        'player.updated' => [
            'tags' => ['players', 'teams', 'public:tournaments', 'public:teams', 'public:players'],
            'keys' => [
                'players:{payload.player_id}',
                'teams:{payload.team_id}:players',
                'public:tournaments:{payload.tournament_id}:*',
                'public:teams:{payload.team_id}',
                'public:players:{payload.team_id}',
                'public:players:{payload.player_id}'
            ]
        ],
        'player.deleted' => [
            'tags' => ['players', 'teams', 'public:tournaments', 'public:teams', 'public:players'],
            'keys' => [
                'players:{payload.player_id}',
                'teams:{payload.team_id}:players',
                'public:tournaments:{payload.tournament_id}:*',
                'public:teams:{payload.team_id}',
                'public:players:{payload.team_id}'
            ]
        ],

        // Generic cache events
        'cache.clear' => [
            'tags' => ['*'],
            'keys' => ['*']
        ],
        'cache.clear_pattern' => [
            'tags' => [],
            'keys' => ['{payload.pattern}']
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration specific to cache invalidation handling.
    |
    */
    'cache' => [
        // Default TTL for cache keys (seconds)
        'default_ttl' => env('TEAM_CACHE_DEFAULT_TTL', 300), // 5 minutes

        // Enable cache tag support
        'tags_enabled' => env('TEAM_CACHE_TAGS_ENABLED', true),

        // Cache driver to use for invalidation
        'driver' => env('TEAM_CACHE_DRIVER', 'redis'),

        // Enable wildcard pattern matching
        'wildcards_enabled' => env('TEAM_CACHE_WILDCARDS_ENABLED', true),

        // Maximum number of keys to process in one batch
        'batch_size' => env('TEAM_CACHE_BATCH_SIZE', 100),

        // Timeout for cache operations (seconds)
        'operation_timeout' => env('TEAM_CACHE_TIMEOUT', 5),
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
];
