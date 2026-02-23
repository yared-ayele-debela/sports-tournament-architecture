<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Support\ApiResponse;

class TournamentRateLimitMiddleware
{
    /**
     * Tournament service rate limiting configurations
     */
    protected array $tournamentLimits = [
        'public_read' => [
            'requests_per_minute' => 100,
            'requests_per_hour' => 2000,
            'burst_capacity' => 20,
            'cache_ttl' => 300, // 5 minutes
        ],
        'protected_read' => [
            'requests_per_minute' => 200,
            'requests_per_hour' => 5000,
            'burst_capacity' => 30,
            'cache_ttl' => 300,
        ],
        'write_operations' => [
            'requests_per_minute' => 30,
            'requests_per_hour' => 500,
            'burst_capacity' => 5,
            'cache_ttl' => 60,
        ],
        'bulk_operations' => [
            'requests_per_minute' => 10,
            'requests_per_hour' => 100,
            'burst_capacity' => 2,
            'cache_ttl' => 60,
        ],
        'search' => [
            'requests_per_minute' => 50,
            'requests_per_hour' => 1000,
            'burst_capacity' => 10,
            'cache_ttl' => 180,
        ],
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $operationType = 'public_read'): mixed
    {
        $config = $this->tournamentLimits[$operationType] ?? $this->tournamentLimits['public_read'];
        $key = $this->generateKey($request, $operationType);
        
        // Check rate limits
        $limitResult = $this->checkRateLimit($key, $config);
        
        if (!$limitResult['allowed']) {
            return $this->rateLimitResponse($limitResult, $config, $operationType);
        }
        
        $response = $next($request);
        
        // Add rate limit headers
        $this->addRateLimitHeaders($response, $limitResult, $config, $operationType);
        
        return $response;
    }

    /**
     * Generate tournament-specific key
     */
    protected function generateKey(Request $request, string $operationType): string
    {
        $identifier = $this->getIdentifier($request);
        $endpoint = $this->getEndpointGroup($request);
        
        return "tournament_rate_limit:{$operationType}:{$endpoint}:{$identifier}";
    }

    /**
     * Get client identifier
     */
    protected function getIdentifier(Request $request): string
    {
        // Try authenticated user first
        if ($request->user()) {
            return "user:" . $request->user()->id;
        }
        
        // Try API key
        $apiKey = $request->header('X-API-Key');
        if ($apiKey) {
            return "api_key:" . hash('sha256', $apiKey);
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
        $method = $request->method();
        
        // Group by resource type
        if (str_contains($path, 'tournaments')) {
            if ($method === 'GET') {
                return 'tournaments_read';
            } else {
                return 'tournaments_write';
            }
        }
        
        if (str_contains($path, 'sports')) {
            if ($method === 'GET') {
                return 'sports_read';
            } else {
                return 'sports_write';
            }
        }
        
        if (str_contains($path, 'venues')) {
            if ($method === 'GET') {
                return 'venues_read';
            } else {
                return 'venues_write';
            }
        }
        
        if (str_contains($path, 'search')) {
            return 'search';
        }
        
        return 'general';
    }

    /**
     * Check rate limits with sliding window and caching
     */
    protected function checkRateLimit(string $key, array $config): array
    {
        $now = Carbon::now();
        
        try {
            // Try to get from cache first
            $cacheKey = $key . ':cache';
            $cached = Redis::get($cacheKey);
            
            if ($cached) {
                $cachedData = json_decode($cached, true);
                if ($cachedData['expires_at'] > $now->timestamp) {
                    return $cachedData['data'];
                }
            }
            
            // Check minute limit
            $minuteResult = $this->checkWindow($key . ':minute', $config['requests_per_minute'], 60);
            
            // Check hour limit
            $hourResult = $this->checkWindow($key . ':hour', $config['requests_per_hour'], 3600);
            
            // Determine if request is allowed
            $allowed = $minuteResult['allowed'] && $hourResult['allowed'];
            $retryAfter = max($minuteResult['retry_after'] ?? 0, $hourResult['retry_after'] ?? 0);
            
            $result = [
                'allowed' => $allowed,
                'retry_after' => $retryAfter,
                'minute_remaining' => $minuteResult['remaining'],
                'hour_remaining' => $hourResult['remaining'],
                'minute_reset' => $minuteResult['reset_time'],
                'hour_reset' => $hourResult['reset_time'],
            ];
            
            // Cache the result
            $cacheData = [
                'data' => $result,
                'expires_at' => $now->timestamp + min(60, $config['cache_ttl']),
            ];
            Redis::setex($cacheKey, $config['cache_ttl'], json_encode($cacheData));
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Tournament rate limit check failed', ['key' => $key, 'error' => $e->getMessage()]);
            
            // Fail open
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
     * Check rate limit window with burst capacity
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
            Redis::expire($key, $windowSeconds + 60);
            
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
    protected function rateLimitResponse(array $limitResult, array $config, string $operationType): JsonResponse
    {
        $retryAfter = $limitResult['retry_after'];
        
        return response()->json([
            'success' => false,
            'message' => "Tournament service rate limit exceeded for {$operationType} operations.",
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $retryAfter,
            'operation_type' => $operationType,
            'limits' => [
                'minute' => $config['requests_per_minute'],
                'hour' => $config['requests_per_hour'],
            ],
            'reset_times' => [
                'minute' => $limitResult['minute_reset'],
                'hour' => $limitResult['hour_reset'],
            ],
            'timestamp' => now()->toISOString(),
        ], 429)->header('Retry-After', $retryAfter);
    }

    /**
     * Add rate limit headers to response
     */
    protected function addRateLimitHeaders($response, array $limitResult, array $config, string $operationType): void
    {
        $headers = [
            'X-Tournament-RateLimit-Operation' => $operationType,
            'X-Tournament-RateLimit-Limit-Minute' => $config['requests_per_minute'],
            'X-Tournament-RateLimit-Remaining-Minute' => max(0, $limitResult['minute_remaining']),
            'X-Tournament-RateLimit-Reset-Minute' => $limitResult['minute_reset'],
            'X-Tournament-RateLimit-Limit-Hour' => $config['requests_per_hour'],
            'X-Tournament-RateLimit-Remaining-Hour' => max(0, $limitResult['hour_remaining']),
            'X-Tournament-RateLimit-Reset-Hour' => $limitResult['hour_reset'],
        ];
        
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }
    }
}
