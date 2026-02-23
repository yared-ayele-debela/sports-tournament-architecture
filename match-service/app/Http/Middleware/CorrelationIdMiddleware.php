<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CorrelationIdMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get correlation ID from header or generate a new one
        $correlationId = $request->header('X-Request-ID') ?? Str::uuid()->toString();

        // Add correlation ID to log context
        Log::withContext(['correlation_id' => $correlationId]);

        // Process the request
        $response = $next($request);

        // Add correlation ID to response header
        $response->headers->set('X-Request-ID', $correlationId);

        return $response;
    }
}
