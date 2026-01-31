<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Public API CORS Middleware
 * 
 * Adds CORS headers to all public API responses.
 * Allows all origins, GET and OPTIONS methods, and standard headers.
 */
class PublicCorsMiddleware
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
        if (!method_exists($response, 'header')) {
            return $response;
        }

        // Allow all origins for public API
        $response->headers->set('Access-Control-Allow-Origin', '*');

        // Allow methods
        $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');

        // Allow headers
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept');

        // Cache preflight response for 24 hours
        $response->headers->set('Access-Control-Max-Age', '86400');

        // Allow credentials (if needed in future)
        // $response->headers->set('Access-Control-Allow-Credentials', 'true');

        return $response;
    }
}
