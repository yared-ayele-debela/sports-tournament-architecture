<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Force JSON Response Middleware
 *
 * Forces Accept: application/json header on all requests
 * to ensure all responses are JSON formatted.
 */
class ForceJsonResponseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Force Accept header to application/json
        $request->headers->set('Accept', 'application/json');

        // Process the request
        $response = $next($request);

        // Ensure response is JSON if it's a JSON response
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $response->headers->set('Content-Type', 'application/json');
        }

        return $response;
    }
}
