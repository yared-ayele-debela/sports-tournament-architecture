<?php

namespace App\Support;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Standardized API Response Handler
 *
 * Provides consistent error and success response formats across all services
 */
class ApiResponse
{
    /**
     * Success response
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $code
     * @param int|null $cacheTtl Cache TTL in seconds (null = no cache metadata)
     * @return JsonResponse
     */
    public static function success($data = null, ?string $message = null, int $code = 200, ?int $cacheTtl = null): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message ?? 'Success',
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ];

        // Add cache metadata if TTL is provided
        if ($cacheTtl !== null) {
            $response['cached'] = true;
            $response['cache_expires_at'] = now()->addSeconds($cacheTtl)->toISOString();
        }

        return response()->json($response, $code);
    }

    /**
     * Error response
     *
     * @param string $message
     * @param int $code
     * @param mixed $errors
     * @param string|null $errorCode
     * @return JsonResponse
     */
    public static function error(string $message, int $code = 400, $errors = null, ?string $errorCode = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if ($errorCode !== null) {
            $response['error_code'] = $errorCode;
        }

        return response()->json($response, $code);
    }

    /**
     * Paginated response
     *
     * @param LengthAwarePaginator $paginator
     * @param string $message
     * @param int|null $cacheTtl Cache TTL in seconds (null = no cache metadata)
     * @return JsonResponse
     */
    public static function paginated(LengthAwarePaginator $paginator, string $message, ?int $cacheTtl = null): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more' => $paginator->hasMorePages(),
                'has_previous' => $paginator->currentPage() > 1,
            ],
            'timestamp' => now()->toISOString(),
        ];

        // Add cache metadata if TTL is provided
        if ($cacheTtl !== null) {
            $response['cached'] = true;
            $response['cache_expires_at'] = now()->addSeconds($cacheTtl)->toISOString();
        }

        return response()->json($response);
    }

    /**
     * Not found response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, 404, null, 'RESOURCE_NOT_FOUND');
    }

    /**
     * Validation error response
     *
     * @param mixed $errors
     * @param string $message
     * @return JsonResponse
     */
    public static function validationError($errors, string $message = 'Validation failed'): JsonResponse
    {
        return self::error($message, 422, $errors, 'VALIDATION_ERROR');
    }

    /**
     * Unauthorized response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, 401, null, 'UNAUTHORIZED');
    }

    /**
     * Forbidden response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return self::error($message, 403, null, 'FORBIDDEN');
    }

    /**
     * Method not allowed response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function methodNotAllowed(string $message = 'Method not allowed'): JsonResponse
    {
        return self::error($message, 405, null, 'METHOD_NOT_ALLOWED');
    }

    /**
     * Server error response
     *
     * @param string $message
     * @param \Throwable|null $exception
     * @return JsonResponse
     */
    public static function serverError(string $message = 'Internal server error', ?\Throwable $exception = null): JsonResponse
    {
        if ($exception !== null) {
            Log::error($message, [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'trace' => config('app.debug') ? $exception->getTraceAsString() : null,
            ]);
        }

        return self::error($message, 500, null, 'INTERNAL_SERVER_ERROR');
    }

    /**
     * Service unavailable response
     *
     * @param string $message
     * @param string|null $serviceName
     * @return JsonResponse
     */
    public static function serviceUnavailable(string $message = 'Service unavailable', ?string $serviceName = null): JsonResponse
    {
        $errorCode = $serviceName ? "SERVICE_UNAVAILABLE_{$serviceName}" : 'SERVICE_UNAVAILABLE';
        return self::error($message, 503, null, $errorCode);
    }

    /**
     * Too many requests response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function tooManyRequests(string $message = 'Too many requests'): JsonResponse
    {
        return self::error($message, 429, null, 'RATE_LIMIT_EXCEEDED');
    }

    /**
     * Created response
     *
     * @param mixed $data
     * @param string|null $message
     * @return JsonResponse
     */
    public static function created($data = null, ?string $message = null): JsonResponse
    {
        return self::success($data, $message ?? 'Resource created successfully', 201);
    }

    /**
     * No content response
     *
     * @param string|null $message
     * @return JsonResponse
     */
    public static function noContent(?string $message = null): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message ?? 'Operation completed successfully',
            'data' => null,
            'timestamp' => now()->toISOString(),
        ], 204);
    }

    /**
     * Bad request response
     *
     * @param string $message
     * @param mixed $errors
     * @return JsonResponse
     */
    public static function badRequest(string $message = 'Bad request', $errors = null): JsonResponse
    {
        return self::error($message, 400, $errors, 'BAD_REQUEST');
    }
}
