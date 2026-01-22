<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Microservice Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for internal microservices
    |
    */

    'tournament' => [
        'url' => env('TOURNAMENT_SERVICE_URL', 'http://localhost:8002'),
        'timeout' => env('TOURNAMENT_SERVICE_TIMEOUT', 10),
        'retries' => env('TOURNAMENT_SERVICE_RETRIES', 3),
    ],

    'auth' => [
        'url' => env('AUTH_SERVICE_URL', 'http://localhost:8001'),
        'timeout' => env('AUTH_SERVICE_TIMEOUT', 10),
        'retries' => env('AUTH_SERVICE_RETRIES', 3),
    ],

    'team' => [
        'url' => env('TEAM_SERVICE_URL', 'http://localhost:8003'),
        'timeout' => env('TEAM_SERVICE_TIMEOUT', 10),
        'retries' => env('TEAM_SERVICE_RETRIES', 3),
    ],

    'match' => [
        'url' => env('MATCH_SERVICE_URL', 'http://localhost:8004'),
        'timeout' => env('MATCH_SERVICE_TIMEOUT', 10),
        'retries' => env('MATCH_SERVICE_RETRIES', 3),
    ],

    'results' => [
        'url' => env('RESULTS_SERVICE_URL', 'http://localhost:8005'),
        'timeout' => env('RESULTS_SERVICE_TIMEOUT', 10),
        'retries' => env('RESULTS_SERVICE_RETRIES', 3),
    ],

];
