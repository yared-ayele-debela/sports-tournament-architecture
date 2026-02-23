<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Carbon\Carbon;

/**
 * Public API Rate Limiting Middleware
 *
 * Limits public API requests to 100 requests per minute per IP address.
 * Returns 429 Too Many Requests if limit is exceeded.
 * Adds rate limit headers to all responses.
 */
class PublicRateLimitMiddleware
{
    /**
     * Maximum number of requests allowed per minute
     */
    protected int $maxAttempts = 100;

    /**
     * Time window in minutes
     */
    protected int $decayMinutes = 1;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $key = $this->generateKey($request);

        // Check if rate limit is exceeded
        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);
            $resetTime = Carbon::now()->addSeconds($retryAfter)->timestamp;

            return $this->rateLimitExceededResponse($retryAfter, $resetTime);
        }

        // Increment the rate limit counter
        RateLimiter::hit($key, $this->decayMinutes * 60);

        // Process the request
        $response = $next($request);

        // Add rate limit headers to response
        $this->addRateLimitHeaders($response, $key);

        return $response;
    }

    /**
     * Generate rate limit key based on IP address
     *
     * @param Request $request
     * @return string
     */
    protected function generateKey(Request $request): string
    {
        $ip = $request->ip();
        $route = $request->route()?->getName() ?? $request->path();

        return "public_api_rate_limit:{$ip}:{$route}";
    }

    /**
     * Add rate limit headers to response
     *
     * @param mixed $response
     * @param string $key
     * @return void
     */
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

    /**
     * Return rate limit exceeded response
     *
     * @param int $retryAfter Seconds until retry is allowed
     * @param int $resetTime Unix timestamp when limit resets
     * @return JsonResponse
     */
    protected function rateLimitExceededResponse(int $retryAfter, int $resetTime): JsonResponse
    {
        $response = response()->json([
            'success' => false,
            'message' => 'Too many requests. Please try again later.',
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $retryAfter,
            'timestamp' => Carbon::now()->toISOString(),
        ], 429);

        // Add rate limit headers even for rate-limited responses
        $response->headers->set('X-RateLimit-Limit', (string) $this->maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', '0');
        $response->headers->set('X-RateLimit-Reset', (string) $resetTime);
        $response->headers->set('Retry-After', (string) $retryAfter);

        return $response;
    }
}
