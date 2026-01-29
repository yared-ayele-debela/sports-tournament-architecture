<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Responses\ApiResponse;

class UserBasedRateLimitMiddleware
{
    /**
     * User tier configurations
     */
    protected array $userTiers = [
        'free' => [
            'requests_per_minute' => 30,
            'requests_per_hour' => 500,
            'requests_per_day' => 5000,
            'burst_capacity' => 5,
        ],
        'basic' => [
            'requests_per_minute' => 100,
            'requests_per_hour' => 2000,
            'requests_per_day' => 20000,
            'burst_capacity' => 15,
        ],
        'premium' => [
            'requests_per_minute' => 500,
            'requests_per_hour' => 10000,
            'requests_per_day' => 100000,
            'burst_capacity' => 50,
        ],
        'enterprise' => [
            'requests_per_minute' => 2000,
            'requests_per_hour' => 50000,
            'requests_per_day' => 500000,
            'burst_capacity' => 200,
        ],
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $defaultTier = 'free'): mixed
    {
        if (!$request->user()) {
            // Fallback to IP-based rate limiting for unauthenticated requests
            return app(RateLimitMiddleware::class)->handle($request, $next, $defaultTier);
        }

        $userTier = $this->getUserTier($request->user());
        $config = $this->userTiers[$userTier] ?? $this->userTiers[$defaultTier];
        
        $key = $this->generateUserKey($request->user(), $userTier);
        
        // Check rate limits
        $limitResult = $this->checkUserRateLimit($key, $config);
        
        if (!$limitResult['allowed']) {
            return $this->rateLimitResponse($limitResult, $config, $userTier);
        }
        
        $response = $next($request);
        
        // Add rate limit headers
        $this->addRateLimitHeaders($response, $limitResult, $config, $userTier);
        
        return $response;
    }

    /**
     * Get user tier based on subscription or role
     */
    protected function getUserTier($user): string
    {
        // Check user subscription or role
        if (method_exists($user, 'subscription')) {
            $subscription = $user->subscription('default');
            if ($subscription && $subscription->active()) {
                return match($subscription->plan) {
                    'basic' => 'basic',
                    'premium' => 'premium',
                    'enterprise' => 'enterprise',
                    default => 'free',
                };
            }
        }
        
        // Check user role
        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole('admin') || $user->hasRole('enterprise')) {
                return 'enterprise';
            }
            if ($user->hasRole('premium')) {
                return 'premium';
            }
            if ($user->hasRole('basic')) {
                return 'basic';
            }
        }
        
        return 'free';
    }

    /**
     * Generate user-based rate limiting key
     */
    protected function generateUserKey($user, string $tier): string
    {
        return "user_rate_limit:{$tier}:{$user->id}";
    }

    /**
     * Check user rate limits
     */
    protected function checkUserRateLimit(string $key, array $config): array
    {
        $now = Carbon::now();
        
        try {
            // Check multiple time windows
            $minuteResult = $this->checkWindow($key . ':minute', $config['requests_per_minute'], 60);
            $hourResult = $this->checkWindow($key . ':hour', $config['requests_per_hour'], 3600);
            $dayResult = $this->checkWindow($key . ':day', $config['requests_per_day'], 86400);
            
            // Determine if request is allowed
            $allowed = $minuteResult['allowed'] && $hourResult['allowed'] && $dayResult['allowed'];
            $retryAfter = max(
                $minuteResult['retry_after'] ?? 0,
                $hourResult['retry_after'] ?? 0,
                $dayResult['retry_after'] ?? 0
            );
            
            return [
                'allowed' => $allowed,
                'retry_after' => $retryAfter,
                'minute_remaining' => $minuteResult['remaining'],
                'hour_remaining' => $hourResult['remaining'],
                'day_remaining' => $dayResult['remaining'],
                'minute_reset' => $minuteResult['reset_time'],
                'hour_reset' => $hourResult['reset_time'],
                'day_reset' => $dayResult['reset_time'],
            ];
        } catch (\Exception $e) {
            Log::error('User rate limit check failed', ['key' => $key, 'error' => $e->getMessage()]);
            
            // Fail open
            return [
                'allowed' => true,
                'retry_after' => 0,
                'minute_remaining' => $config['requests_per_minute'],
                'hour_remaining' => $config['requests_per_hour'],
                'day_remaining' => $config['requests_per_day'],
                'minute_reset' => $now->addMinute()->timestamp,
                'hour_reset' => $now->addHour()->timestamp,
                'day_reset' => $now->addDay()->timestamp,
            ];
        }
    }

    /**
     * Check rate limit window
     */
    protected function checkWindow(string $key, int $maxRequests, int $windowSeconds): array
    {
        $now = Carbon::now();
        $windowStart = $now->timestamp - $windowSeconds;
        
        // Clean old entries
        Redis::zremrangebyscore($key, 0, $windowStart);
        
        $currentRequests = Redis::zcard($key);
        
        if ($currentRequests < $maxRequests) {
            // Add current request
            Redis::zadd($key, $now->timestamp, $now->timestamp . ':' . uniqid());
            Redis::expire($key, $windowSeconds + 60); // Extra buffer
            
            return [
                'allowed' => true,
                'remaining' => $maxRequests - $currentRequests - 1,
                'reset_time' => $now->addSeconds($windowSeconds)->timestamp,
            ];
        }
        
        // Calculate retry after
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
    protected function rateLimitResponse(array $limitResult, array $config, string $tier)
    {
        $retryAfter = $limitResult['retry_after'];
        
        return response()->json([
            'success' => false,
            'message' => "Your {$tier} tier rate limit has been exceeded. Please upgrade your plan or try again later.",
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $retryAfter,
            'tier' => $tier,
            'limits' => [
                'minute' => $config['requests_per_minute'],
                'hour' => $config['requests_per_hour'],
                'day' => $config['requests_per_day'],
            ],
            'reset_times' => [
                'minute' => $limitResult['minute_reset'],
                'hour' => $limitResult['hour_reset'],
                'day' => $limitResult['day_reset'],
            ],
            'timestamp' => now()->toISOString(),
        ], 429)->header('Retry-After', $retryAfter);
    }

    /**
     * Add rate limit headers to response
     */
    protected function addRateLimitHeaders($response, array $limitResult, array $config, string $tier): void
    {
        $headers = [
            'X-RateLimit-Tier' => $tier,
            'X-RateLimit-Limit-Minute' => $config['requests_per_minute'],
            'X-RateLimit-Remaining-Minute' => max(0, $limitResult['minute_remaining']),
            'X-RateLimit-Reset-Minute' => $limitResult['minute_reset'],
            'X-RateLimit-Limit-Hour' => $config['requests_per_hour'],
            'X-RateLimit-Remaining-Hour' => max(0, $limitResult['hour_remaining']),
            'X-RateLimit-Reset-Hour' => $limitResult['hour_reset'],
            'X-RateLimit-Limit-Day' => $config['requests_per_day'],
            'X-RateLimit-Remaining-Day' => max(0, $limitResult['day_remaining']),
            'X-RateLimit-Reset-Day' => $limitResult['day_reset'],
        ];
        
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }
    }
}
