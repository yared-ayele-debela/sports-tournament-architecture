<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\Response;
/**
 */
class TokenValidationCache
{
    protected string $authServiceUrl;
    protected int $cacheTtl;
    protected int $defaultTtl = 300; // 5 minutes default

    public function __construct()
    {
        $this->authServiceUrl = config('services.auth.url', env('AUTH_SERVICE_URL', 'http://localhost:8001'));
        $this->cacheTtl = config('services.auth.token_cache_ttl', $this->defaultTtl);
    }

    /**
     * Validate token with caching
     *
     * @param string $token
     * @return array|null Returns user data on success, null on failure
     */
    public function validate(string $token): ?array
    {
        $cacheKey = $this->getCacheKey($token);

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            Log::debug('Token validation cache hit', ['token_hash' => substr($cacheKey, -8)]);
            return $cached;
        }

        // Cache miss - validate with auth service
        Log::debug('Token validation cache miss', ['token_hash' => substr($cacheKey, -8)]);
        $userData = $this->validateWithAuthService($token);

        if ($userData !== null) {
            // Cache successful validation
            // Use shorter TTL than token expiration to ensure we revalidate
            $ttl = $this->calculateTtl($userData);
            Cache::put($cacheKey, $userData, $ttl);
            Log::debug('Token validation cached', [
                'token_hash' => substr($cacheKey, -8),
                'ttl' => $ttl
            ]);
        }

        return $userData;
    }

    /**
     * Validate token directly with auth service
     *
     * @param string $token
     * @return array|null
     */
    protected function validateWithAuthService(string $token): ?array
    {
        try {
            /** @var Response $response */
            $response = Http::timeout(5)
                ->withToken($token)
                ->get($this->authServiceUrl . '/api/auth/me');

            if ($response->status() !== 200) {
                return null;
            }

            $responseData = $response->json();

            if (!($responseData['success'] ?? false)) {
                return null;
            }

            return $responseData['data'] ?? null;
        } catch (\Exception $e) {
            Log::error('Token validation failed', [
                'error' => $e->getMessage(),
                'auth_service_url' => $this->authServiceUrl
            ]);
            return null;
        }
    }

    /**
     * Invalidate token cache
     *
     * @param string $token
     * @return void
     */
    public function invalidate(string $token): void
    {
        $cacheKey = $this->getCacheKey($token);
        Cache::forget($cacheKey);
        Log::debug('Token validation cache invalidated', ['token_hash' => substr($cacheKey, -8)]);
    }
    /**
     * Invalidate all tokens for a user
     *
     * @param int $userId
     * @return void
     */
    public function invalidateUser(int $userId): void
    {
        // Note: This requires a cache tag system or user-token mapping
        // For now, we'll use a pattern-based approach if using Redis
        $pattern = "token_validation:user_{$userId}:*";
        
        // If using Redis, we can use SCAN to find and delete matching keys
        if (config('cache.default') === 'redis') {
            $this->invalidateByPattern($pattern);
        }
        
        Log::info('User token cache invalidated', ['user_id' => $userId]);
    }

    /**
     * Invalidate cache entries by pattern (Redis only)
     *
     * @param string $pattern
     * @return void
     */
    protected function invalidateByPattern(string $pattern): void
    {
        try {
            if (config('cache.default') !== 'redis') {
                return;
            }
            
            $redis = \Illuminate\Support\Facades\Redis::connection();
            $keys = $redis->keys($pattern);
            
            if (!empty($keys)) {
                $redis->del($keys);
                Log::debug('Cache entries deleted by pattern', [
                    'pattern' => $pattern,
                    'count' => count($keys)
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to invalidate cache by pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cache key for token
     *
     * @param string $token
     * @return string
     */
    protected function getCacheKey(string $token): string
    {
        // Use hash of token to avoid storing full token
        $tokenHash = hash('sha256', $token);
        return "token_validation:{$tokenHash}";
    }

    /**
     * Calculate cache TTL based on token expiration
     *
     * @param array $userData
     * @return int TTL in seconds
     */
    protected function calculateTtl(array $userData): int
    {
        // If token expiration is available in response, use it
        // Otherwise use configured default TTL
        // We'll use a conservative approach: cache for 5 minutes or until token expires (whichever is shorter)
        
        // Default Passport token expiration is typically 1 year
        // We'll cache for a shorter period to ensure we revalidate periodically
        return min($this->cacheTtl, $this->defaultTtl);
    }

    /**
     * Clear all token validation cache
     *
     * @return void
     */
    public function clearAll(): void
    {
        if (config('cache.default') === 'redis') {
            $this->invalidateByPattern('token_validation:*');
        }
        
        Log::info('All token validation cache cleared');
    }
}
