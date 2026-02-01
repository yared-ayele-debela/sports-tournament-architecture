<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * CORS Middleware for Admin Dashboard and API Access
 *
 * Adds CORS headers to all API responses.
 * Allows all origins, all HTTP methods, and Authorization header.
 */
class CorsMiddleware
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
        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            return $this->handlePreflight($request);
        }

        // Process the request
        $response = $next($request);

        // Add CORS headers to response
        return $this->addCorsHeaders($response);
    }

    /**
     * Handle CORS preflight request
     *
     * @param Request $request
     * @return Response
     */
    protected function handlePreflight(Request $request): Response
    {
        $response = response('', 200);

        return $this->addCorsHeaders($response);
    }

    /**
     * Add CORS headers to response
     *
     * @param mixed $response
     * @return mixed
     */
    protected function addCorsHeaders($response)
    {
        // Allow all origins for development
        $response->headers->set('Access-Control-Allow-Origin', '*');

        // Allow all methods needed for CRUD operations
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');

        // Allow headers including Authorization for Bearer tokens
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With');

        // Cache preflight response for 24 hours
        $response->headers->set('Access-Control-Max-Age', '86400');

        return $response;
    }
}
