<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Support\ApiResponse;

class AuthRateLimitMiddleware
{
    /**
     * Authentication-specific rate limiting configurations
     */
    protected array $authLimits = [
        'login' => [
            'requests_per_minute' => 5,
            'requests_per_hour' => 20,
            'burst_capacity' => 2,
            'penalty_minutes' => 15,
            'progressive_penalty' => true,
        ],
        'register' => [
            'requests_per_minute' => 3,
            'requests_per_hour' => 10,
            'burst_capacity' => 1,
            'penalty_minutes' => 30,
            'progressive_penalty' => true,
        ],
        'password_reset' => [
            'requests_per_minute' => 3,
            'requests_per_hour' => 5,
            'burst_capacity' => 1,
            'penalty_minutes' => 60,
            'progressive_penalty' => true,
        ],
        'token_refresh' => [
            'requests_per_minute' => 10,
            'requests_per_hour' => 100,
            'burst_capacity' => 5,
            'penalty_minutes' => 5,
            'progressive_penalty' => false,
        ],
        'user_validation' => [
            'requests_per_minute' => 60,
            'requests_per_hour' => 1000,
            'burst_capacity' => 10,
            'penalty_minutes' => 1,
            'progressive_penalty' => false,
        ],
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $authType = 'login'): mixed
    {
        $config = $this->authLimits[$authType] ?? $this->authLimits['login'];
        $key = $this->generateKey($request, $authType);
        
        // Check for existing penalties
        if ($this->isPenalized($key, $config)) {
            return $this->penaltyResponse($key, $config);
        }
        
        // Check rate limits
        $limitResult = $this->checkRateLimit($key, $config);
        
        if (!$limitResult['allowed']) {
            // Apply penalty for exceeding limits
            $this->applyPenalty($key, $config);
            return $this->rateLimitResponse($limitResult, $config, $authType);
        }
        
        $response = $next($request);
        
        // Add rate limit headers
        $this->addRateLimitHeaders($response, $limitResult, $config, $authType);
        
        return $response;
    }

    /**
     * Generate authentication-specific key
     */
    protected function generateKey(Request $request, string $authType): string
    {
        $identifier = $this->getIdentifier($request);
        return "auth_rate_limit:{$authType}:{$identifier}";
    }

    /**
     * Get client identifier for auth operations
     */
    protected function getIdentifier(Request $request): string
    {
        // For auth endpoints, prioritize email/username for account-level limits
        $email = $request->input('email');
        if ($email) {
            return "email:" . strtolower($email);
        }
        
        $username = $request->input('username');
        if ($username) {
            return "username:" . strtolower($username);
        }
        
        // Fallback to IP
        return "ip:" . $request->ip();
    }

    /**
     * Check if client is currently penalized
     */
    protected function isPenalized(string $key, array $config): bool
    {
        try {
            $penaltyKey = $key . ':penalty';
            $penaltyEnd = Redis::get($penaltyKey);
            
            if ($penaltyEnd && Carbon::now()->timestamp < (int) $penaltyEnd) {
                return true;
            }
            
            // Clean up expired penalty
            if ($penaltyEnd) {
                Redis::del($penaltyKey);
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Penalty check failed', ['key' => $key, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Apply penalty to client
     */
    protected function applyPenalty(string $key, array $config): void
    {
        try {
            $penaltyKey = $key . ':penalty';
            $violationKey = $key . ':violations';
            
            // Get violation count
            $violations = (int) Redis::get($violationKey) ?: 0;
            $violations++;
            
            // Calculate penalty duration
            $penaltyMinutes = $config['penalty_minutes'];
            
            if ($config['progressive_penalty']) {
                // Exponential backoff: 2^violations * base_penalty
                $penaltyMinutes = min($penaltyMinutes * pow(2, $violations - 1), 1440); // Max 24 hours
            }
            
            $penaltyEnd = Carbon::now()->addMinutes($penaltyMinutes)->timestamp;
            
            // Set penalty
            Redis::setex($penaltyKey, $penaltyMinutes * 60, $penaltyEnd);
            
            // Update violation count (expires after 24 hours)
            Redis::setex($violationKey, 86400, $violations);
            
            Log::warning('Auth rate limit penalty applied', [
                'key' => $key,
                'violations' => $violations,
                'penalty_minutes' => $penaltyMinutes,
                'penalty_end' => $penaltyEnd,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to apply penalty', ['key' => $key, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Check rate limits with sliding window
     */
    protected function checkRateLimit(string $key, array $config): array
    {
        $now = Carbon::now();
        
        try {
            // Check minute limit
            $minuteResult = $this->checkWindow($key . ':minute', $config['requests_per_minute'], 60);
            
            // Check hour limit
            $hourResult = $this->checkWindow($key . ':hour', $config['requests_per_hour'], 3600);
            
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
            Log::error('Auth rate limit check failed', ['key' => $key, 'error' => $e->getMessage()]);
            
            // Fail open for auth endpoints (but log the error)
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
     * Generate penalty response
     */
    protected function penaltyResponse(string $key, array $config): JsonResponse
    {
        try {
            $penaltyKey = $key . ':penalty';
            $penaltyEnd = (int) Redis::get($penaltyKey);
            $remainingSeconds = max(0, $penaltyEnd - Carbon::now()->timestamp);
            
            return response()->json([
                'success' => false,
                'message' => 'Too many failed authentication attempts. Your account has been temporarily locked for security reasons.',
                'error_code' => 'ACCOUNT_LOCKED',
                'locked_until' => $penaltyEnd,
                'remaining_seconds' => $remainingSeconds,
                'retry_after' => $remainingSeconds,
                'timestamp' => now()->toISOString(),
            ], 423)->header('Retry-After', $remainingSeconds);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Too many failed authentication attempts. Please try again later.',
                'error_code' => 'ACCOUNT_LOCKED',
                'retry_after' => 300, // 5 minutes default
                'timestamp' => now()->toISOString(),
            ], 423);
        }
    }

    /**
     * Generate rate limit response
     */
    protected function rateLimitResponse(array $limitResult, array $config, string $authType): JsonResponse
    {
        $retryAfter = $limitResult['retry_after'];
        
        return response()->json([
            'success' => false,
            'message' => "Too many {$authType} attempts. Please try again later.",
            'error_code' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $retryAfter,
            'auth_type' => $authType,
            'limits' => [
                'minute' => $config['requests_per_minute'],
                'hour' => $config['requests_per_hour'],
            ],
            'timestamp' => now()->toISOString(),
        ], 429)->header('Retry-After', $retryAfter);
    }

    /**
     * Add rate limit headers to response
     */
    protected function addRateLimitHeaders($response, array $limitResult, array $config, string $authType): void
    {
        $headers = [
            'X-Auth-RateLimit-Type' => $authType,
            'X-Auth-RateLimit-Limit-Minute' => $config['requests_per_minute'],
            'X-Auth-RateLimit-Remaining-Minute' => max(0, $limitResult['minute_remaining']),
            'X-Auth-RateLimit-Reset-Minute' => $limitResult['minute_reset'],
            'X-Auth-RateLimit-Limit-Hour' => $config['requests_per_hour'],
            'X-Auth-RateLimit-Remaining-Hour' => max(0, $limitResult['hour_remaining']),
            'X-Auth-RateLimit-Reset-Hour' => $limitResult['hour_reset'],
        ];
        
        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }
    }
}
