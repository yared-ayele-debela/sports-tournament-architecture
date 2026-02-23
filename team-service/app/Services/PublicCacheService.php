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
    protected int $defaultLiveTtl = 300; // 5 minutes
    protected int $defaultStaticTtl = 3600; // 1 hour
    protected string $keyPrefix = 'public_api';
    protected string $statsKey = 'public_cache:stats';

    public function remember(
        string $key,
        ?int $ttl,
        Closure $callback,
        array $tags = [],
        string $dataType = 'live'
    ) {
        $finalTtl = $ttl ?? ($dataType === 'static' ? $this->defaultStaticTtl : $this->defaultLiveTtl);
        if (empty($tags)) {
            $tags = ['public-api'];
        }

        try {
            if ($this->supportsTags()) {
                return Cache::tags($tags)->remember($key, $finalTtl, function () use ($callback, $key) {
                    $this->recordCacheMiss($key);
                    Log::debug('Public cache miss', ['key' => $key]);
                    return $callback();
                });
            }

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
            return $callback();
        }
    }

    public function forget(string $key): bool
    {
        try {
            return Cache::forget($key);
        } catch (Throwable $e) {
            Log::error('Failed to forget cache key', ['key' => $key, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function forgetByTag(string $tag): bool
    {
        return $this->forgetByTags([$tag]);
    }

    public function forgetByTags(array $tags): bool
    {
        try {
            if (!$this->supportsTags()) {
                Log::warning('Cache tags not supported', ['tags' => $tags, 'driver' => config('cache.default')]);
                return false;
            }

            Cache::tags($tags)->flush();
            $this->recordInvalidation($tags);

            Log::info('Public cache invalidated by tags', ['tags' => $tags, 'count' => count($tags)]);
            return true;
        } catch (Throwable $e) {
            Log::error('Failed to invalidate cache by tags', ['tags' => $tags, 'error' => $e->getMessage()]);
            return false;
        }
    }

    public function generateKey(string $prefix, ?array $params = null): string
    {
        $key = "{$this->keyPrefix}:{$prefix}";
        if ($params !== null && !empty($params)) {
            ksort($params);
            $paramsHash = md5(serialize($params));
            $key .= ":{$paramsHash}";
        }
        return $key;
    }

    public function generateKeyFromRoute(string $route, ?array $queryParams = null): string
    {
        $routeName = str_replace(['.', '/'], ':', $route);
        $routeName = trim($routeName, ':');
        return $this->generateKey($routeName, $queryParams);
    }

    public function getCacheStats(): array
    {
        try {
            $stats = Cache::get($this->statsKey, [
                'hits' => 0,
                'misses' => 0,
                'invalidations' => 0,
                'last_reset' => now()->toISOString(),
            ]);

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

    public function resetStats(): bool
    {
        try {
            Cache::put($this->statsKey, [
                'hits' => 0,
                'misses' => 0,
                'invalidations' => 0,
                'last_reset' => now()->toISOString(),
            ], 86400);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    protected function recordCacheHit(string $key): void
    {
        try {
            $stats = Cache::get($this->statsKey, ['hits' => 0, 'misses' => 0, 'invalidations' => 0, 'last_reset' => now()->toISOString()]);
            $stats['hits']++;
            Cache::put($this->statsKey, $stats, 86400);
        } catch (Throwable $e) {
            // Silently fail
        }
    }

    protected function recordCacheMiss(string $key): void
    {
        try {
            $stats = Cache::get($this->statsKey, ['hits' => 0, 'misses' => 0, 'invalidations' => 0, 'last_reset' => now()->toISOString()]);
            $stats['misses']++;
            Cache::put($this->statsKey, $stats, 86400);
        } catch (Throwable $e) {
            // Silently fail
        }
    }

    protected function recordInvalidation(array $tags): void
    {
        try {
            $stats = Cache::get($this->statsKey, ['hits' => 0, 'misses' => 0, 'invalidations' => 0, 'last_reset' => now()->toISOString()]);
            $stats['invalidations']++;
            Cache::put($this->statsKey, $stats, 86400);
        } catch (Throwable $e) {
            // Silently fail
        }
    }

    protected function supportsTags(): bool
    {
        $driver = config('cache.default');
        return in_array($driver, ['redis', 'memcached']);
    }

    public function forgetByPattern(string $pattern): int
    {
        try {
            if (config('cache.default') !== 'redis') {
                return 0;
            }

            $redis = Redis::connection();
            $keys = $redis->keys($pattern);

            if (!empty($keys)) {
                $deleted = $redis->del($keys);
                Log::info('Public cache invalidated by pattern', ['pattern' => $pattern, 'keys_count' => count($keys), 'deleted' => $deleted]);
                return $deleted;
            }

            return 0;
        } catch (Throwable $e) {
            Log::error('Failed to invalidate cache by pattern', ['pattern' => $pattern, 'error' => $e->getMessage()]);
            return 0;
        }
    }

    public function clearAll(): bool
    {
        try {
            if (config('cache.default') === 'redis') {
                $pattern = "{$this->keyPrefix}:*";
                $deleted = $this->forgetByPattern($pattern);
                return $deleted > 0;
            }
            return $this->forgetByTags(['public-api']);
        } catch (Throwable $e) {
            Log::error('Failed to clear all public cache', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
