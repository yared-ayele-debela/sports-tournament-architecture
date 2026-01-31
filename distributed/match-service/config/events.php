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
    | Event handlers for processing incoming events from queue
    | Maps event types to handler classes
    |
    */
    'handlers' => [
        'team.created' => \App\Handlers\TeamCreatedHandler::class,
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
        // Match Events (local to match-service)
        'match.created' => [
            'tags' => ['matches', 'public:matches', 'public:tournaments'],
            'keys' => [
                'matches',
                'matches:*',
                'public:tournaments:{payload.tournament_id}:*',
                'public:matches:{payload.match_id}',
                'public:matches:*'
            ]
        ],
        'match.updated' => [
            'tags' => ['matches', 'public:matches', 'public:tournaments'],
            'keys' => [
                'matches:{payload.match_id}',
                'matches:*',
                'public:tournaments:{payload.tournament_id}:*',
                'public:matches:{payload.match_id}',
                'public:matches:*'
            ]
        ],
        'match.completed' => [
            'tags' => ['matches', 'public:matches', 'public:tournaments', 'public:standings'],
            'keys' => [
                'matches:{payload.match_id}',
                'matches:*',
                'public:tournaments:{payload.tournament_id}:*',
                'public:matches:{payload.match_id}',
                'public:standings:*'
            ]
        ],
        'match.started' => [
            'tags' => ['matches', 'public:matches', 'public:tournaments', 'public:live'],
            'keys' => [
                'matches:{payload.match_id}',
                'matches:*',
                'live_matches',
                'public:tournaments:{payload.tournament_id}:*',
                'public:matches:{payload.match_id}',
                'public:live'
            ]
        ],
        'match.status.changed' => [
            'tags' => ['matches', 'public:matches', 'public:tournaments', 'public:live'],
            'keys' => [
                'matches:{payload.match_id}',
                'public:matches:{payload.match_id}',
                'public:matches:*',
                'public:tournaments:{payload.tournament_id}:*',
                'public:live'
            ]
        ],
        'match.event.recorded' => [
            'tags' => ['matches', 'public:matches', 'public:live'],
            'keys' => [
                'matches:{payload.match_id}:events',
                'match:{payload.match_id}:events',
                'live_matches',
                'public:matches:{payload.match_id}:events',
                'public:live'
            ]
        ],
        'match.event_added' => [
            'tags' => ['matches', 'public:matches', 'public:live'],
            'keys' => [
                'matches:{payload.match_id}',
                'match:{payload.match_id}:events',
                'live_matches',
                'public:tournaments:*',
                'public:matches:{payload.match_id}:events'
            ]
        ],
        'match.score.updated' => [
            'tags' => ['matches', 'public:matches', 'public:tournaments', 'public:live'],
            'keys' => [
                'matches:{payload.match_id}',
                'matches:*',
                'live_matches',
                'public:tournaments:{payload.tournament_id}:*',
                'public:matches:{payload.match_id}',
                'public:live'
            ]
        ],
        'match.cancelled' => [
            'tags' => ['matches', 'public:matches', 'public:tournaments'],
            'keys' => [
                'matches:{payload.match_id}',
                'matches:*',
                'public:tournaments:{payload.tournament_id}:*',
                'public:matches:{payload.match_id}'
            ]
        ],
        'match.postponed' => [
            'tags' => ['matches', 'public:matches', 'public:tournaments'],
            'keys' => [
                'matches:{payload.match_id}',
                'matches:*',
                'public:tournaments:{payload.tournament_id}:*',
                'public:matches:{payload.match_id}'
            ]
        ],

        // Team Events (from team-service)
        'team.created' => [
            'tags' => ['teams', 'public:tournaments', 'public:matches'],
            'keys' => [
                'teams',
                'teams:*',
                'public:tournaments:{payload.tournament_id}:*',
                'public:matches:*'
            ]
        ],
        'team.updated' => [
            'tags' => ['teams', 'public:tournaments', 'public:matches'],
            'keys' => [
                'teams:{payload.team_id}',
                'teams:*',
                'public:tournaments:{payload.tournament_id}:*',
                'public:matches:*'
            ]
        ],
        'team.deleted' => [
            'tags' => ['teams', 'public:tournaments', 'public:matches'],
            'keys' => [
                'teams:*',
                'public:tournaments:*',
                'public:matches:*'
            ]
        ],

        // Tournament Events (from tournament-service)
        'sports.tournament.created' => [
            'tags' => ['tournaments', 'public:tournaments', 'public:matches'],
            'keys' => [
                'tournaments',
                'tournaments:*',
                'public:tournaments',
                'public:tournaments:*',
                'public:matches:*'
            ]
        ],
        'sports.tournament.updated' => [
            'tags' => ['tournaments', 'public:tournaments', 'public:matches'],
            'keys' => [
                'tournaments:{payload.tournament_id}',
                'tournaments:*',
                'public:tournaments:{payload.tournament_id}',
                'public:tournaments:*',
                'public:matches:*'
            ]
        ],
        'sports.tournament.status.changed' => [
            'tags' => ['tournaments', 'public:tournaments', 'public:matches'],
            'keys' => [
                'tournaments:{payload.tournament_id}',
                'public:tournaments:{payload.tournament_id}',
                'public:tournaments:*',
                'public:matches:*'
            ]
        ],
        'sports.tournament.deleted' => [
            'tags' => ['tournaments', 'public:tournaments', 'public:matches'],
            'keys' => [
                'tournaments:*',
                'public:tournaments:*',
                'public:matches:*'
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
        'default_ttl' => env('MATCH_CACHE_DEFAULT_TTL', 300), // 5 minutes

        // TTL based on match status
        'status_ttl' => [
            'scheduled' => env('MATCH_CACHE_TTL_SCHEDULED', 600),    // 10 minutes
            'in_progress' => env('MATCH_CACHE_TTL_LIVE', 30),       // 30 seconds
            'completed' => env('MATCH_CACHE_TTL_COMPLETED', 3600),   // 1 hour
            'cancelled' => env('MATCH_CACHE_TTL_CANCELLED', 3600),   // 1 hour
            'postponed' => env('MATCH_CACHE_TTL_POSTPONED', 600),    // 10 minutes
        ],

        // TTL for specific endpoints
        'endpoint_ttl' => [
            'events' => env('MATCH_CACHE_TTL_EVENTS', 30),           // 30 seconds
            'live' => env('MATCH_CACHE_TTL_LIVE_ENDPOINT', 10),     // 10 seconds
            'upcoming' => env('MATCH_CACHE_TTL_UPCOMING', 600),     // 10 minutes
        ],

        // Enable cache tag support
        'tags_enabled' => env('MATCH_CACHE_TAGS_ENABLED', true),

        // Cache driver to use for invalidation
        'driver' => env('MATCH_CACHE_DRIVER', 'redis'),

        // Enable wildcard pattern matching
        'wildcards_enabled' => env('MATCH_CACHE_WILDCARDS_ENABLED', true),

        // Maximum number of keys to process in one batch
        'batch_size' => env('MATCH_CACHE_BATCH_SIZE', 100),

        // Timeout for cache operations (seconds)
        'operation_timeout' => env('MATCH_CACHE_TIMEOUT', 5),
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
