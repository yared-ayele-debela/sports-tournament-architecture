<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;

class PublicRateLimitMiddleware
{
    protected int $maxAttempts = 100;
    protected int $decayMinutes = 1;

    public function handle(Request $request, Closure $next)
    {
        $key = $this->generateKey($request);

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);
            $resetTime = Carbon::now()->addSeconds($retryAfter)->timestamp;
            return $this->rateLimitExceededResponse($retryAfter, $resetTime);
        }

        RateLimiter::hit($key, $this->decayMinutes * 60);
        $response = $next($request);
        $this->addRateLimitHeaders($response, $key);

        return $response;
    }

    protected function generateKey(Request $request): string
    {
        $ip = $request->ip();
        $route = $request->route()?->getName() ?? $request->path();
        return "public_api_rate_limit:{$ip}:{$route}";
    }

    protected function addRateLimitHeaders($response, string $key): void
    {
        if (!method_exists($response, 'header')) {
            return;
        }

        $remaining = max(0, $this->maxAttempts - RateLimiter::attempts($key));
        $resetTime = Carbon::now()->addMinutes($this->decayMinutes)->timestamp;

        $response->headers->set('X-RateLimit-Limit', (string) $this->maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', (string) $remaining);
        $response->headers->set('X-RateLimit-Reset', (string) $resetTime);
    }

    protected function rateLimitExceededResponse(int $retryAfter, int $resetTime): JsonResponse
    {
        $response = response()->json([
            'success' => false,
            'message' => 'Too many requests. Please try again later.',
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $retryAfter,
            'timestamp' => Carbon::now()->toISOString(),
        ], 429);

        $response->headers->set('X-RateLimit-Limit', (string) $this->maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', '0');
        $response->headers->set('X-RateLimit-Reset', (string) $resetTime);
        $response->headers->set('Retry-After', (string) $retryAfter);

        return $response;
    }
}
