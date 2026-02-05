<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use Closure;
use Throwable;

/**
 * Base Public API Controller
 *
 * Provides a foundation for all public API endpoints with:
 * - No authentication required
 * - Heavy caching with configurable TTL
 * - Rate limiting for public access
 * - Consistent JSON response format
 * - Error handling for service failures
 * - Cache tags for easy invalidation
 * - CORS headers for public access
 *
 * This controller can be extended by all microservices for their public endpoints.
 */
abstract class PublicApiController extends Controller
{
    /**
     * Default cache TTL in seconds
     */
    protected int $defaultCacheTtl = 300; // 5 minutes

    /**
     * Default cache tags for this controller
     */
    protected array $defaultCacheTags = ['public-api'];

    /**
     * Rate limiting configuration
     */
    protected array $rateLimitConfig = [
        'max_attempts' => 60,      // requests per minute
        'decay_minutes' => 1,      // time window
        'key_prefix' => 'public_api',
    ];

    /**
     * Constructor - Apply rate limiting middleware
     */
    public function __construct()
    {
        // Rate limiting is handled via middleware in routes
        // This can be overridden in child controllers
    }

    /**
     * Success response with optional caching metadata
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $statusCode
     * @param int|null $cacheTtl Cache TTL in seconds (null = no cache info)
     * @return JsonResponse
     */
    protected function successResponse(
        $data = null,
        ?string $message = null,
        int $statusCode = 200,
        ?int $cacheTtl = null
    ): JsonResponse {
        $response = ApiResponse::success($data, $message ?? 'Success', $statusCode, $cacheTtl);
        return $this->addCorsHeaders($response);
    }

    /**
     * Error response
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed $errors Additional error details
     * @param string|null $errorCode Machine-readable error code
     * @return JsonResponse
     */
    protected function errorResponse(
        string $message,
        int $statusCode = 400,
        $errors = null,
        ?string $errorCode = null
    ): JsonResponse {
        $response = ApiResponse::error($message, $statusCode, $errors, $errorCode);
        return $this->addCorsHeaders($response);
    }

    /**
     * Cache response with tags support
     *
     * @param string $key Cache key
     * @param Closure $callback Callback to execute if cache miss
     * @param int|null $ttl Cache TTL in seconds (null = use default)
     * @param array|null $tags Cache tags (null = use default tags)
     * @return mixed
     */
    protected function cacheResponse(
        string $key,
        Closure $callback,
        ?int $ttl = null,
        ?array $tags = null
    ) {
        $ttl = $ttl ?? $this->defaultCacheTtl;
        $tags = $tags ?? $this->defaultCacheTags;

        try {
            // Check if cache tags are supported (Redis, Memcached)
            if (config('cache.default') === 'redis' || config('cache.default') === 'memcached') {
                return Cache::tags($tags)->remember($key, $ttl, function () use ($callback, $key) {
                    Log::debug('Cache miss', ['key' => $key]);
                    return $callback();
                });
            }

            // Fallback for cache drivers that don't support tags
            return Cache::remember($key, $ttl, function () use ($callback, $key) {
                Log::debug('Cache miss', ['key' => $key]);
                return $callback();
            });
        } catch (Throwable $e) {
            Log::warning('Cache operation failed, executing callback directly', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            // If cache fails, execute callback directly
            return $callback();
        }
    }

    /**
     * Handle service failures with proper error responses
     *
     * @param Throwable $exception
     * @param string|null $customMessage
     * @param string|null $serviceName
     * @return JsonResponse
     */
    protected function handleServiceFailure(
        Throwable $exception,
        ?string $customMessage = null,
        ?string $serviceName = null
    ): JsonResponse {
        $serviceName = $serviceName ?? 'external service';
        $message = $customMessage ?? "Failed to communicate with {$serviceName}";

        Log::error('Service failure', [
            'service' => $serviceName,
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'trace' => config('app.debug') ? $exception->getTraceAsString() : null,
        ]);

        // Determine appropriate status code based on exception type
        $statusCode = 503; // Service Unavailable by default
        $errorCode = 'SERVICE_UNAVAILABLE';

        if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
            $statusCode = 503;
            $errorCode = 'SERVICE_CONNECTION_ERROR';
        } elseif ($exception instanceof \Illuminate\Http\Client\RequestException) {
            $statusCode = 502; // Bad Gateway
            $errorCode = 'SERVICE_BAD_GATEWAY';
        } elseif ($exception instanceof \GuzzleHttp\Exception\ServerException) {
            $statusCode = 502;
            $errorCode = 'SERVICE_SERVER_ERROR';
        } elseif ($exception instanceof \GuzzleHttp\Exception\ClientException) {
            $statusCode = 502;
            $errorCode = 'SERVICE_CLIENT_ERROR';
        }

        return $this->errorResponse($message, $statusCode, null, $errorCode);
    }

    /**
     * Add CORS headers to response
     *
     * @param JsonResponse $response
     * @return JsonResponse
     */
    protected function addCorsHeaders(JsonResponse $response): JsonResponse
    {
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Max-Age', '86400'); // 24 hours

        return $response;
    }

    /**
     * Check rate limit for current request
     *
     * @param Request $request
     * @param string|null $key Custom rate limit key (default: IP-based)
     * @return array ['allowed' => bool, 'remaining' => int, 'retry_after' => int|null]
     */
    protected function checkRateLimit(Request $request, ?string $key = null): array
    {
        $key = $key ?? $this->generateRateLimitKey($request);

        $maxAttempts = $this->rateLimitConfig['max_attempts'];
        $decayMinutes = $this->rateLimitConfig['decay_minutes'];

        // Check if rate limit is exceeded
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);
            return [
                'allowed' => false,
                'remaining' => 0,
                'retry_after' => $retryAfter,
            ];
        }

        // Increment the rate limit counter
        RateLimiter::hit($key, $decayMinutes * 60);

        // Calculate remaining attempts
        $remaining = max(0, $maxAttempts - RateLimiter::attempts($key));

        return [
            'allowed' => true,
            'remaining' => $remaining,
            'retry_after' => null,
        ];
    }

    /**
     * Generate rate limit key based on request
     *
     * @param Request $request
     * @return string
     */
    protected function generateRateLimitKey(Request $request): string
    {
        $prefix = $this->rateLimitConfig['key_prefix'];
        $ip = $request->ip();
        $route = $request->route()?->getName() ?? $request->path();

        return "{$prefix}:{$ip}:{$route}";
    }

    /**
     * Invalidate cache by tags
     *
     * @param array $tags Cache tags to invalidate
     * @return bool
     */
    protected function invalidateCacheByTags(array $tags): bool
    {
        try {
            if (config('cache.default') === 'redis' || config('cache.default') === 'memcached') {
                Cache::tags($tags)->flush();
                Log::info('Cache invalidated by tags', ['tags' => $tags]);
                return true;
            }

            Log::warning('Cache tags not supported by current cache driver', [
                'driver' => config('cache.default'),
            ]);
            return false;
        } catch (Throwable $e) {
            Log::error('Failed to invalidate cache by tags', [
                'tags' => $tags,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Invalidate cache by key pattern
     *
     * @param string $pattern Cache key pattern (supports wildcards for Redis)
     * @return bool
     */
    protected function invalidateCacheByPattern(string $pattern): bool
    {
        try {
            if (config('cache.default') === 'redis') {
                $redis = Redis::connection();
                $keys = $redis->keys($pattern);

                if (!empty($keys)) {
                    $redis->del($keys);
                    Log::info('Cache invalidated by pattern', [
                        'pattern' => $pattern,
                        'keys_count' => count($keys),
                    ]);
                    return true;
                }
            }

            Log::warning('Cache pattern invalidation only supported for Redis', [
                'driver' => config('cache.default'),
            ]);
            return false;
        } catch (Throwable $e) {
            Log::error('Failed to invalidate cache by pattern', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get cache key with prefix
     *
     * @param string $key
     * @param array|null $params Additional parameters to include in key
     * @return string
     */
    protected function getCacheKey(string $key, ?array $params = null): string
    {
        $prefix = 'public_api';
        $controllerName = class_basename(static::class);
        $baseKey = "{$prefix}:{$controllerName}:{$key}";

        if ($params !== null && !empty($params)) {
            $paramsHash = md5(serialize($params));
            return "{$baseKey}:" . $paramsHash;
        }

        return $baseKey;
    }

    /**
     * Handle OPTIONS request for CORS preflight
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function options(Request $request): JsonResponse
    {
        return $this->addCorsHeaders(
            response()->json([], 200)
        );
    }
}
