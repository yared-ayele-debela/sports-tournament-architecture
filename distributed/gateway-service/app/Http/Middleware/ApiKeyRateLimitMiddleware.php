<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Http\Responses\ApiResponse;

class ApiKeyRateLimitMiddleware
{
    /**
     * API Key tier configurations
     */
    protected array $apiTiers = [
        'trial' => [
            'requests_per_minute' => 10,
            'requests_per_hour' => 200,
            'requests_per_day' => 1000,
            'burst_capacity' => 3,
        ],
        'starter' => [
            'requests_per_minute' => 50,
            'requests_per_hour' => 1000,
            'requests_per_day' => 10000,
            'burst_capacity' => 10,
        ],
        'professional' => [
            'requests_per_minute' => 200,
            'requests_per_hour' => 5000,
            'requests_per_day' => 50000,
            'burst_capacity' => 25,
        ],
        'business' => [
            'requests_per_minute' => 1000,
            'requests_per_hour' => 20000,
            'requests_per_day' => 200000,
            'burst_capacity' => 100,
        ],
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $defaultTier = 'trial'): mixed
    {
        $apiKey = $this->extractApiKey($request);
        
        if (!$apiKey) {
            return ApiResponse::unauthorized('This endpoint requires a valid API key. Please include X-API-Key header.');
        }

        $apiTier = $this->getApiKeyTier($apiKey);
        $config = $this->apiTiers[$apiTier] ?? $this->apiTiers[$defaultTier];
        
        $key = $this->generateApiKeyKey($apiKey, $apiTier);
        
        // Check rate limits
        $limitResult = $this->checkApiKeyRateLimit($key, $config);
        
        if (!$limitResult['allowed']) {
            return $this->rateLimitResponse($limitResult, $config, $apiTier, $apiKey);
        }
        
        $response = $next($request);
        
        // Add rate limit headers
        $this->addRateLimitHeaders($response, $limitResult, $config, $apiTier);
        
        return $response;
    }

    /**
     * Extract API key from request
     */
    protected function extractApiKey(Request $request): ?string
    {
        // Try header first
        $apiKey = $request->header('X-API-Key');
        if ($apiKey) {
            return $apiKey;
        }
        
        // Try query parameter
        $apiKey = $request->query('api_key');
        if ($apiKey) {
            return $apiKey;
        }
        
        // Try authorization header (Bearer token)
        $authorization = $request->header('Authorization');
        if ($authorization && str_starts_with($authorization, 'Bearer ')) {
            return substr($authorization, 7);
        }
        
        return null;
    }

    /**
     * Get API key tier from database or cache
     */
    protected function getApiKeyTier(string $apiKey): string
    {
        try {
            // Cache API key tier for performance
            $cacheKey = "api_key_tier:" . hash('sha256', $apiKey);
            $cachedTier = Redis::get($cacheKey);
            
            if ($cachedTier) {
                return $cachedTier;
            }
            
            // In a real implementation, you would query your database
            // For now, we'll use a simple hash-based tier assignment
            $tier = $this->determineTierFromKey($apiKey);
            
            // Cache for 1 hour
            Redis::setex($cacheKey, 3600, $tier);
            
            return $tier;
        } catch (\Exception $e) {
            Log::error('Failed to get API key tier', ['api_key_hash' => hash('sha256', $apiKey), 'error' => $e->getMessage()]);
            return 'trial';
        }
    }

    /**
     * Determine tier from API key (simplified implementation)
     */
    protected function determineTierFromKey(string $apiKey): string
    {
        $hash = crc32($apiKey);
        $normalized = abs($hash) % 100;
        
        if ($normalized < 5) return 'business';      // 5%
        if ($normalized < 20) return 'professional';  // 15%
        if ($normalized < 50) return 'starter';       // 30%
        return 'trial';                               // 50%
    }

    /**
     * Generate API key-based rate limiting key
     */
    protected function generateApiKeyKey(string $apiKey, string $tier): string
    {
        $keyHash = hash('sha256', $apiKey);
        return "api_key_rate_limit:{$tier}:{$keyHash}";
    }

    /**
     * Check API key rate limits
     */
    protected function checkApiKeyRateLimit(string $key, array $config): array
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
            Log::error('API key rate limit check failed', ['key' => $key, 'error' => $e->getMessage()]);
            
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
    protected function rateLimitResponse(array $limitResult, array $config, string $tier, string $apiKey)
    {
        $retryAfter = $limitResult['retry_after'];
        
        return response()->json([
            'success' => false,
            'message' => "Your {$tier} API key rate limit has been exceeded.",
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
            'api_key_prefix' => substr($apiKey, 0, 8) . '...',
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
