<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Channel
    |--------------------------------------------------------------------------
    |
    | The single Redis channel used for all events. All events are published
    | and subscribed through this channel. Event routing is done by event_type.
    |
    */
    'default_channel' => env('EVENTS_DEFAULT_CHANNEL', 'sports.events'),

    /*
    |--------------------------------------------------------------------------
    | Service Configuration
    |--------------------------------------------------------------------------
    |
    | Service-specific configuration
    |
    */
    'service' => [
        'name' => env('EVENTS_SERVICE_NAME', 'results-service'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Handlers
    |--------------------------------------------------------------------------
    |
    | Event handlers for processing incoming events.
    | Events are routed by event_type field in the event payload.
    |
    */
    'handlers' => [
        'match.completed' => \App\Handlers\MatchCompletedHandler::class,
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
    'publish' => [
        'sports.standings.updated',
        'sports.statistics.updated',
    ],
];
