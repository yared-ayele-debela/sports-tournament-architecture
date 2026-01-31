<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Closure;
use Throwable;

/**
 * Public Cache Service
 *
 * Manages caching for public API endpoints with:
 * - Tag-based cache invalidation
 * - Configurable TTL (5 min for live data, 1 hour for static)
 * - Cache key generation based on route + query params
 * - Batch cache invalidation
 * - Cache statistics tracking
 */
class PublicCacheService
{
    /**
     * Default TTL for live data (in seconds)
     */
    protected int $defaultLiveTtl = 300; // 5 minutes

    /**
     * Default TTL for static data (in seconds)
     */
    protected int $defaultStaticTtl = 3600; // 1 hour

    /**
     * Cache key prefix
     */
    protected string $keyPrefix = 'public_api';

    /**
     * Cache statistics storage key
     */
    protected string $statsKey = 'public_cache:stats';

    /**
     * Remember a value in cache with tags
     *
     * @param string $key Cache key
     * @param int|null $ttl Cache TTL in seconds (null = use default for data type)
     * @param Closure $callback Callback to execute if cache miss
     * @param array $tags Cache tags for invalidation
     * @param string $dataType 'live' or 'static' (affects default TTL)
     * @return mixed
     */
    public function remember(
        string $key,
        ?int $ttl,
        Closure $callback,
        array $tags = [],
        string $dataType = 'live'
    ) {
        // Determine TTL
        $finalTtl = $ttl ?? ($dataType === 'static' ? $this->defaultStaticTtl : $this->defaultLiveTtl);

        // Ensure we have at least a default tag
        if (empty($tags)) {
            $tags = ['public-api'];
        }

        try {
            // Check if cache tags are supported
            if ($this->supportsTags()) {
                return Cache::tags($tags)->remember($key, $finalTtl, function () use ($callback, $key) {
                    $this->recordCacheMiss($key);
                    Log::debug('Public cache miss', ['key' => $key]);
                    return $callback();
                });
            }

            // Fallback for cache drivers that don't support tags
            return Cache::remember($key, $finalTtl, function () use ($callback, $key) {
                $this->recordCacheMiss($key);
                Log::debug('Public cache miss (no tags)', ['key' => $key]);
                return $callback();
            });
        } catch (Throwable $e) {
            Log::warning('Public cache operation failed, executing callback directly', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            // If cache fails, execute callback directly
            return $callback();
        }
    }

    /**
     * Forget a specific cache key
     *
     * @param string $key Cache key to forget
     * @return bool
     */
    public function forget(string $key): bool
    {
        try {
            $forgotten = Cache::forget($key);

            if ($forgotten) {
                Log::debug('Public cache key forgotten', ['key' => $key]);
            }

            return $forgotten;
        } catch (Throwable $e) {
            Log::error('Failed to forget cache key', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Forget all cache entries with a specific tag
     *
     * @param string $tag Cache tag
     * @return bool
     */
    public function forgetByTag(string $tag): bool
    {
        return $this->forgetByTags([$tag]);
    }

    /**
     * Forget all cache entries with specific tags
     *
     * @param array $tags Cache tags
     * @return bool
     */
    public function forgetByTags(array $tags): bool
    {
        try {
            if (!$this->supportsTags()) {
                Log::warning('Cache tags not supported, cannot invalidate by tags', [
                    'tags' => $tags,
                    'driver' => config('cache.default'),
                ]);
                return false;
            }

            Cache::tags($tags)->flush();

            $this->recordInvalidation($tags);

            Log::info('Public cache invalidated by tags', [
                'tags' => $tags,
                'count' => count($tags),
            ]);

            return true;
        } catch (Throwable $e) {
            Log::error('Failed to invalidate cache by tags', [
                'tags' => $tags,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Generate cache key based on prefix and parameters
     *
     * @param string $prefix Key prefix (e.g., 'tournaments', 'matches')
     * @param array|null $params Additional parameters (query params, IDs, etc.)
     * @return string
     */
    public function generateKey(string $prefix, ?array $params = null): string
    {
        $key = "{$this->keyPrefix}:{$prefix}";

        if ($params !== null && !empty($params)) {
            // Sort params to ensure consistent keys
            ksort($params);
            $paramsHash = md5(serialize($params));
            $key .= ":{$paramsHash}";
        }

        return $key;
    }

    /**
     * Generate cache key from route and request
     *
     * @param string $route Route name or path
     * @param array|null $queryParams Query parameters
     * @return string
     */
    public function generateKeyFromRoute(string $route, ?array $queryParams = null): string
    {
        // Normalize route name
        $routeName = str_replace(['.', '/'], ':', $route);
        $routeName = trim($routeName, ':');

        return $this->generateKey($routeName, $queryParams);
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public function getCacheStats(): array
    {
        try {
            $stats = Cache::get($this->statsKey, [
                'hits' => 0,
                'misses' => 0,
                'invalidations' => 0,
                'last_reset' => now()->toISOString(),
            ]);

            // Calculate hit rate
            $total = $stats['hits'] + $stats['misses'];
            $hitRate = $total > 0 ? ($stats['hits'] / $total) * 100 : 0;

            return [
                'hits' => $stats['hits'],
                'misses' => $stats['misses'],
                'invalidations' => $stats['invalidations'],
                'hit_rate' => round($hitRate, 2),
                'total_requests' => $total,
                'last_reset' => $stats['last_reset'],
            ];
        } catch (Throwable $e) {
            Log::error('Failed to get cache statistics', [
                'error' => $e->getMessage(),
            ]);

            return [
                'hits' => 0,
                'misses' => 0,
                'invalidations' => 0,
                'hit_rate' => 0,
                'total_requests' => 0,
                'last_reset' => now()->toISOString(),
            ];
        }
    }

    /**
     * Reset cache statistics
     *
     * @return bool
     */
    public function resetStats(): bool
    {
        try {
            Cache::put($this->statsKey, [
                'hits' => 0,
                'misses' => 0,
                'invalidations' => 0,
                'last_reset' => now()->toISOString(),
            ], 86400); // Store for 24 hours

            Log::info('Public cache statistics reset');

            return true;
        } catch (Throwable $e) {
            Log::error('Failed to reset cache statistics', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Record a cache hit
     *
     * @param string $key Cache key
     * @return void
     */
    protected function recordCacheHit(string $key): void
    {
        try {
            $stats = Cache::get($this->statsKey, [
                'hits' => 0,
                'misses' => 0,
                'invalidations' => 0,
                'last_reset' => now()->toISOString(),
            ]);

            $stats['hits']++;
            Cache::put($this->statsKey, $stats, 86400);
        } catch (Throwable $e) {
            // Silently fail statistics recording
        }
    }

    /**
     * Record a cache miss
     *
     * @param string $key Cache key
     * @return void
     */
    protected function recordCacheMiss(string $key): void
    {
        try {
            $stats = Cache::get($this->statsKey, [
                'hits' => 0,
                'misses' => 0,
                'invalidations' => 0,
                'last_reset' => now()->toISOString(),
            ]);

            $stats['misses']++;
            Cache::put($this->statsKey, $stats, 86400);
        } catch (Throwable $e) {
            // Silently fail statistics recording
        }
    }

    /**
     * Record a cache invalidation
     *
     * @param array $tags Tags that were invalidated
     * @return void
     */
    protected function recordInvalidation(array $tags): void
    {
        try {
            $stats = Cache::get($this->statsKey, [
                'hits' => 0,
                'misses' => 0,
                'invalidations' => 0,
                'last_reset' => now()->toISOString(),
            ]);

            $stats['invalidations']++;
            Cache::put($this->statsKey, $stats, 86400);
        } catch (Throwable $e) {
            // Silently fail statistics recording
        }
    }

    /**
     * Check if cache driver supports tags
     *
     * @return bool
     */
    protected function supportsTags(): bool
    {
        $driver = config('cache.default');
        return in_array($driver, ['redis', 'memcached']);
    }

    /**
     * Invalidate cache by pattern (Redis only)
     *
     * @param string $pattern Cache key pattern
     * @return int Number of keys deleted
     */
    public function forgetByPattern(string $pattern): int
    {
        try {
            if (config('cache.default') !== 'redis') {
                Log::warning('Cache pattern invalidation only supported for Redis', [
                    'driver' => config('cache.default'),
                ]);
                return 0;
            }

            $redis = Redis::connection();
            $keys = $redis->keys($pattern);

            if (!empty($keys)) {
                $deleted = $redis->del($keys);

                Log::info('Public cache invalidated by pattern', [
                    'pattern' => $pattern,
                    'keys_count' => count($keys),
                    'deleted' => $deleted,
                ]);

                return $deleted;
            }

            return 0;
        } catch (Throwable $e) {
            Log::error('Failed to invalidate cache by pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Get all cache keys matching a pattern (Redis only)
     *
     * @param string $pattern Cache key pattern
     * @return array
     */
    public function getKeysByPattern(string $pattern): array
    {
        try {
            if (config('cache.default') !== 'redis') {
                return [];
            }

            $redis = Redis::connection();
            return $redis->keys($pattern);
        } catch (Throwable $e) {
            Log::error('Failed to get cache keys by pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Clear all public API cache
     *
     * @return bool
     */
    public function clearAll(): bool
    {
        try {
            if (config('cache.default') === 'redis') {
                $pattern = "{$this->keyPrefix}:*";
                $deleted = $this->forgetByPattern($pattern);

                Log::info('All public cache cleared', [
                    'keys_deleted' => $deleted,
                ]);

                return $deleted > 0;
            }

            // For other drivers, try to flush by tags
            return $this->forgetByTags(['public-api']);
        } catch (Throwable $e) {
            Log::error('Failed to clear all public cache', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
