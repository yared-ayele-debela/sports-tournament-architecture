<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Event Channels
    |--------------------------------------------------------------------------
    |
    | Channels to subscribe to for incoming events
    | Gateway Service subscribes to ALL events for monitoring and cache invalidation
    |
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
        'sports.match.deleted',
        'sports.match.started',
        'sports.match.completed',
        'sports.match.status.changed',
        'sports.match.event.recorded',
        
        // Team service events
        'sports.team.created',
        'sports.team.updated',
        'sports.team.deleted',
        'sports.player.created',
        'sports.player.updated',
        'sports.player.deleted',
        
        // Results service events
        'sports.standings.updated',
        'sports.statistics.updated',
        'sports.tournament.recalculated',
        'sports.standings.recalculated',
        
        // Sport and Venue events
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
    | Gateway Service uses handlers for monitoring and cache invalidation
    |
    */
    'handlers' => [
        'monitoring' => [
            'class' => \App\Services\Events\Handlers\MonitoringEventHandler::class,
            'description' => 'Handles all events for logging, monitoring, and metrics tracking'
        ],
        
        'cache_invalidation' => [
            'class' => \App\Services\Events\Handlers\CacheInvalidationHandler::class,
            'description' => 'Handles cache invalidation based on events from other services'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for event monitoring and metrics
    |
    */
    'monitoring' => [
        /*
        |--------------------------------------------------------------------------
        | Metrics Configuration
        |--------------------------------------------------------------------------
        |
        | Configuration for event metrics collection
        |
        */
        'metrics' => [
            'enabled' => env('EVENT_MONITORING_ENABLED', true),
            'cache_ttl' => env('EVENT_METRICS_TTL', 3600), // 1 hour
            'latency_threshold_ms' => env('EVENT_LATENCY_THRESHOLD', 5000), // 5 seconds
            'max_latency_measurements' => env('MAX_LATENCY_MEASUREMENTS', 100),
        ],

        /*
        |--------------------------------------------------------------------------
        | Logging Configuration
        |--------------------------------------------------------------------------
        |
        | Configuration for event logging
        |
        */
        'logging' => [
            'enabled' => env('EVENT_LOGGING_ENABLED', true),
            'channel' => env('EVENT_LOG_CHANNEL', 'monitoring'),
            'log_payload' => env('EVENT_LOG_PAYLOAD', false), // Set to true to log full payload
            'log_level' => env('EVENT_LOG_LEVEL', 'info'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Invalidation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for cache invalidation strategies
    |
    */
    'cache_invalidation' => [
        /*
        |--------------------------------------------------------------------------
        | General Configuration
        |--------------------------------------------------------------------------
        |
        | General cache invalidation settings
        |
        */
        'enabled' => env('CACHE_INVALIDATION_ENABLED', true),
        'batch_size' => env('CACHE_BATCH_SIZE', 100), // Process cache keys in batches
        
        /*
        |--------------------------------------------------------------------------
        | Cache Patterns
        |--------------------------------------------------------------------------
        |
        | Cache key patterns for different entity types
        |
        */
        'patterns' => [
            'tournament' => [
                'gateway:tournament:*',
                'gateway:tournaments:*',
                'gateway:tournament_list:*',
                'gateway:search:tournaments:*'
            ],
            'team' => [
                'gateway:team:*',
                'gateway:teams:*',
                'gateway:team_list:*',
                'gateway:search:teams:*'
            ],
            'player' => [
                'gateway:player:*',
                'gateway:players:*',
                'gateway:team_players:*',
                'gateway:search:players:*'
            ],
            'match' => [
                'gateway:match:*',
                'gateway:matches:*',
                'gateway:tournament_matches:*',
                'gateway:team_matches:*',
                'gateway:search:matches:*'
            ],
            'standings' => [
                'gateway:standings:*',
                'gateway:tournament_standings:*',
                'gateway:search:standings:*'
            ],
            'statistics' => [
                'gateway:statistics:*',
                'gateway:tournament_statistics:*',
                'gateway:team_statistics:*',
                'gateway:search:statistics:*'
            ],
            'sport' => [
                'gateway:sport:*',
                'gateway:sports:*',
                'gateway:sport_list:*',
                'gateway:search:sports:*'
            ],
            'venue' => [
                'gateway:venue:*',
                'gateway:venues:*',
                'gateway:venue_list:*',
                'gateway:search:venues:*'
            ]
        ],

        /*
        |--------------------------------------------------------------------------
        | Invalidation Strategies
        |--------------------------------------------------------------------------
        |
        | Cache invalidation strategies for different event types
        |
        */
        'strategies' => [
            'aggressive' => [
                'description' => 'Invalidate all related cache keys',
                'use_patterns' => true,
                'invalidate_lists' => true,
                'invalidate_search' => true,
            ],
            'selective' => [
                'description' => 'Invalidate only specific cache keys',
                'use_patterns' => false,
                'invalidate_lists' => false,
                'invalidate_search' => false,
            ],
            'smart' => [
                'description' => 'Intelligent cache invalidation based on event payload',
                'use_patterns' => true,
                'invalidate_lists' => true,
                'invalidate_search' => false,
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Performance Configuration
        |--------------------------------------------------------------------------
        |
        | Performance settings for cache invalidation
        |
        */
        'performance' => [
            'async_invalidation' => env('ASYNC_CACHE_INVALIDATION', false),
            'max_keys_per_batch' => env('MAX_KEYS_PER_BATCH', 100),
            'batch_delay_ms' => env('BATCH_DELAY_MS', 10),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Publisher Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for publishing events (if Gateway Service needs to publish)
    |
    */
    'publisher' => [
        'enabled' => env('GATEWAY_EVENT_PUBLISHING_ENABLED', false),
        'service_name' => env('GATEWAY_SERVICE_NAME', 'gateway-service'),
        'retry_attempts' => env('EVENT_PUBLISH_RETRY_ATTEMPTS', 3),
        'retry_delay_ms' => env('EVENT_PUBLISH_RETRY_DELAY', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Subscriber Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the event subscriber
    |
    */
    'subscriber' => [
        'service_name' => env('GATEWAY_SERVICE_NAME', 'gateway-service'),
        'reconnect_delay_ms' => env('EVENT_RECONNECT_DELAY', 5000),
        'max_reconnect_attempts' => env('MAX_RECONNECT_ATTEMPTS', -1), // -1 for infinite
        'connection_timeout' => env('EVENT_CONNECTION_TIMEOUT', 5),
        'read_timeout' => env('EVENT_READ_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Validation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for event validation
    |
    */
    'validation' => [
        'enabled' => env('EVENT_VALIDATION_ENABLED', true),
        'strict_mode' => env('EVENT_VALIDATION_STRICT', false),
        'required_fields' => [
            'event_id',
            'event_type',
            'service',
            'payload',
            'timestamp',
            'version'
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for event security
    |
    */
    'security' => [
        'validate_timestamp' => env('EVENT_VALIDATE_TIMESTAMP', true),
        'max_event_age_minutes' => env('MAX_EVENT_AGE_MINUTES', 60),
        'validate_service' => env('EVENT_VALIDATE_SERVICE', true),
        'allowed_services' => [
            'tournament-service',
            'match-service',
            'team-service',
            'results-service',
            'auth-service',
            'gateway-service'
        ],
    ],
];
