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
 */
abstract class PublicApiController extends Controller
{
    protected int $defaultCacheTtl = 300; // 5 minutes
    protected array $defaultCacheTags = ['public-api'];
    protected array $rateLimitConfig = [
        'max_attempts' => 60,
        'decay_minutes' => 1,
        'key_prefix' => 'public_api',
    ];

    public function __construct()
    {
        // Rate limiting is handled via middleware in routes
    }

    protected function successResponse(
        $data = null,
        ?string $message = null,
        int $statusCode = 200,
        ?int $cacheTtl = null
    ): JsonResponse {
        $response = ApiResponse::success($data, $message ?? 'Success', $statusCode, $cacheTtl);
        return $this->addCorsHeaders($response);
    }

    protected function errorResponse(
        string $message,
        int $statusCode = 400,
        $errors = null,
        ?string $errorCode = null
    ): JsonResponse {
        $response = ApiResponse::error($message, $statusCode, $errors, $errorCode);
        return $this->addCorsHeaders($response);
    }

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
        ]);

        $statusCode = 503;
        $errorCode = 'SERVICE_UNAVAILABLE';

        if ($exception instanceof \Illuminate\Http\Client\ConnectionException) {
            $statusCode = 503;
            $errorCode = 'SERVICE_CONNECTION_ERROR';
        } elseif ($exception instanceof \Illuminate\Http\Client\RequestException) {
            $statusCode = 502;
            $errorCode = 'SERVICE_BAD_GATEWAY';
        }

        return $this->errorResponse($message, $statusCode, null, $errorCode);
    }

    protected function addCorsHeaders(JsonResponse $response): JsonResponse
    {
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept');
        $response->headers->set('Access-Control-Max-Age', '86400');

        return $response;
    }

    public function options(Request $request): JsonResponse
    {
        return $this->addCorsHeaders(response()->json([], 200));
    }
}
