<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PublicCorsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('OPTIONS')) {
            return $this->handlePreflight($request);
        }

        $response = $next($request);
        return $this->addCorsHeaders($response);
    }

    protected function handlePreflight(Request $request): Response
    {
        $response = response('', 200);
        return $this->addCorsHeaders($response);
    }

    protected function addCorsHeaders($response)
    {
        if (!method_exists($response, 'header')) {
            return $response;
        }

        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept');
        $response->headers->set('Access-Control-Max-Age', '86400');

        return $response;
    }
}
