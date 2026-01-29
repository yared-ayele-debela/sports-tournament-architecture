<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Responses\ApiResponse;

class RateLimitMiddleware
{
    /**
     * Rate limiting configurations
     */
    protected array $limits = [
        'public' => [
            'requests_per_minute' => 60,
            'requests_per_hour' => 1000,
            'burst_capacity' => 10,
            'penalty_seconds' => 60,
        ],
        'search' => [
            'requests_per_minute' => 20,
            'requests_per_hour' => 500,
            'burst_capacity' => 5,
            'penalty_seconds' => 120,
        ],
        'live' => [
            'requests_per_minute' => 120,
            'requests_per_hour' => 2000,
            'burst_capacity' => 20,
            'penalty_seconds' => 30,
        ],
        'auth' => [
            'requests_per_minute' => 10,
            'requests_per_hour' => 100,
            'burst_capacity' => 3,
            'penalty_seconds' => 300,
        ],
        'premium' => [
            'requests_per_minute' => 300,
            'requests_per_hour' => 10000,
            'burst_capacity' => 50,
            'penalty_seconds' => 15,
        ],
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $tier = 'public'): mixed
    {
        $config = $this->limits[$tier] ?? $this->limits['public'];
        $key = $this->generateKey($request, $tier);
        
        // Check rate limits
        $limitResult = $this->checkRateLimit($key, $config);
        
        if (!$limitResult['allowed']) {
            return $this->rateLimitResponse($limitResult, $config);
        }
        
        $response = $next($request);
        
        // Add rate limit headers
        $this->addRateLimitHeaders($response, $limitResult, $config);
        
        return $response;
    }

    /**
     * Generate rate limiting key
     */
    protected function generateKey(Request $request, string $tier): string
    {
        $identifier = $this->getIdentifier($request);
        $endpoint = $this->getEndpointGroup($request);
        
        return "rate_limit:{$tier}:{$endpoint}:{$identifier}";
    }

    /**
     * Get client identifier (IP, user ID, or API key)
     */
    protected function getIdentifier(Request $request): string
    {
        // Try API key first
        $apiKey = $request->header('X-API-Key');
        if ($apiKey) {
            return "api_key:" . hash('sha256', $apiKey);
        }
        
        // Try authenticated user
        if ($request->user()) {
            return "user:" . $request->user()->id;
        }
        
        // Fallback to IP
        return "ip:" . $request->ip();
    }

    /**
     * Get endpoint group for rate limiting
     */
    protected function getEndpointGroup(Request $request): string
    {
        $path = $request->path();
        
        if (str_contains($path, 'search')) {
            return 'search';
        }
        
        if (str_contains($path, 'live')) {
            return 'live';
        }
        
        if (str_contains($path, 'auth')) {
            return 'auth';
        }
        
        return 'general';
    }

    /**
     * Check rate limits using sliding window algorithm
     */
    protected function checkRateLimit(string $key, array $config): array
    {
        $now = Carbon::now();
        $minuteKey = $key . ':minute';
        $hourKey = $key . ':hour';
        
        try {
            // Check minute limit
            $minuteResult = $this->checkSlidingWindow($minuteKey, $config['requests_per_minute'], 60, $config['burst_capacity']);
            
            // Check hour limit
            $hourResult = $this->checkSlidingWindow($hourKey, $config['requests_per_hour'], 3600, $config['burst_capacity'] * 10);
            
            // Determine if request is allowed
            $allowed = $minuteResult['allowed'] && $hourResult['allowed'];
            $retryAfter = max($minuteResult['retry_after'] ?? 0, $hourResult['retry_after'] ?? 0);
            
            return [
                'allowed' => $allowed,
                'retry_after' => $retryAfter,
                'minute_remaining' => $minuteResult['remaining'],
                'hour_remaining' => $hourResult['remaining'],
                'minute_reset' => $minuteResult['reset_time'],
                'hour_reset' => $hourResult['reset_time'],
            ];
        } catch (\Exception $e) {
            Log::error('Rate limit check failed', ['key' => $key, 'error' => $e->getMessage()]);
            
            // Fail open - allow request if Redis fails
            return [
                'allowed' => true,
                'retry_after' => 0,
                'minute_remaining' => $config['requests_per_minute'],
                'hour_remaining' => $config['requests_per_hour'],
                'minute_reset' => $now->addMinute()->timestamp,
                'hour_reset' => $now->addHour()->timestamp,
            ];
        }
    }

    /**
     * Check sliding window rate limit
     */
    protected function checkSlidingWindow(string $key, int $maxRequests, int $windowSeconds, int $burstCapacity): array
    {
        $now = Carbon::now();
        $windowStart = $now->timestamp - $windowSeconds;
        
        // Remove old entries
        Redis::zremrangebyscore($key, 0, $windowStart);
        
        // Count current requests
        $currentRequests = Redis::zcard($key);
        
        // Check if we're within burst capacity
        if ($currentRequests < $burstCapacity) {
            // Add current request
            Redis::zadd($key, $now->timestamp, $now->timestamp . ':' . uniqid());
            Redis::expire($key, $windowSeconds);
            
            return [
                'allowed' => true,
                'remaining' => $maxRequests - $currentRequests - 1,
                'reset_time' => $now->addSeconds($windowSeconds)->timestamp,
            ];
        }
        
        // Check if we're within rate limit
        if ($currentRequests < $maxRequests) {
            // Add current request
            Redis::zadd($key, $now->timestamp, $now->timestamp . ':' . uniqid());
            Redis::expire($key, $windowSeconds);
            
            return [
                'allowed' => true,
                'remaining' => $maxRequests - $currentRequests - 1,
                'reset_time' => $now->addSeconds($windowSeconds)->timestamp,
            ];
        }
        
        // Rate limited - calculate retry after
        $oldestRequest = Redis::zrange($key, 0, 0, ['withscores' => true]);
        $retryAfter = 0;
        
        if (!empty($oldestRequest)) {
            $oldestTimestamp = (int) explode(':', array_key_first($oldestRequest))[0];
            $retryAfter = $windowSeconds - ($now->timestamp - $oldestTimestamp);
        }
        
        return [
            'allowed' => false,
            'remaining' => 0,
            'retry_after' => max($retryAfter, 1),
            'reset_time' => $now->addSeconds($retryAfter)->timestamp,
        ];
    }

    /**
     * Generate rate limit response
     */
    protected function rateLimitResponse(array $limitResult, array $config): JsonResponse
    {
        $retryAfter = $limitResult['retry_after'];
        
        // Apply progressive delay
        $delay = $this->calculateProgressiveDelay($retryAfter, $config);
        
        if ($delay > 0) {
            usleep($delay * 1000); // Convert to microseconds
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Rate limit exceeded. Please try again later.',
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $retryAfter,
            'limit_reset' => $limitResult['minute_reset'],
            'timestamp' => now()->toISOString(),
        ], 429)->header('Retry-After', $retryAfter);
    }

    /**
     * Calculate progressive delay based on violation severity
     */
    protected function calculateProgressiveDelay(int $retryAfter, array $config): int
    {
        // Base delay grows with retry after time
        $baseDelay = min($retryAfter * 100, 5000); // Max 5 seconds
        
        // Add randomness to prevent thundering herd
        $jitter = rand(0, $baseDelay * 0.2);
        
        return (int) ($baseDelay + $jitter);
    }

    /**
     * Add rate limit headers to response
     */
    protected function addRateLimitHeaders($response, array $limitResult, array $config): void
    {
        $headers = [
            'X-RateLimit-Limit-Minute' => $config['requests_per_minute'],
            'X-RateLimit-Remaining-Minute' => max(0, $limitResult['minute_remaining']),
            'X-RateLimit-Reset-Minute' => $limitResult['minute_reset'],
            'X-RateLimit-Limit-Hour' => $config['requests_per_hour'],
            'X-RateLimit-Remaining-Hour' => max(0, $limitResult['hour_remaining']),
            'X-RateLimit-Reset-Hour' => $limitResult['hour_reset'],
        ];
        
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }
    }
}
