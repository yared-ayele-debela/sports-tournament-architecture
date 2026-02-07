<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Server-Sent Events Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SSE (Server-Sent Events) connections to prevent
    | server overload and resource exhaustion.
    |
    */

    /*
    | Maximum connection time in seconds
    | After this time, the connection will be closed
    */
    'max_connection_time' => env('SSE_MAX_CONNECTION_TIME', 300), // 5 minutes

    /*
    | Update interval in seconds
    | How often to check for data updates
    */
    'update_interval' => env('SSE_UPDATE_INTERVAL', 5), // 5 seconds

    /*
    | Heartbeat interval in seconds
    | How often to send heartbeat messages
    */
    'heartbeat_interval' => env('SSE_HEARTBEAT_INTERVAL', 30), // 30 seconds

    /*
    | Maximum iterations before force close
    | Safety limit to prevent infinite loops
    */
    'max_iterations' => env('SSE_MAX_ITERATIONS', 3600), // ~30 minutes at 5s intervals

    /*
    | Sleep interval in microseconds
    | Time to sleep between loop iterations
    */
    'sleep_interval' => env('SSE_SLEEP_INTERVAL', 1000000), // 1 second
];
