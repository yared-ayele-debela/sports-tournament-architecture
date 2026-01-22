<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ApiResponse
{
    /**
     * Success response
     *
     * @param mixed $data
     * @param string|null $message
     * @param int $code
     * @return JsonResponse
     */
    public static function success($data = null, ?string $message = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message ?? 'Success',
            'data' => $data,
            'timestamp' => now()->toISOString(),
        ], $code);
    }

    /**
     * Error response
     *
     * @param string $message
     * @param int $code
     * @param mixed $errors
     * @return JsonResponse
     */
    public static function error(string $message, int $code = 400, $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'timestamp' => now()->toISOString(),
        ], $code);
    }

    /**
     * Paginated response
     *
     * @param mixed $data
     * @param array $meta
     * @return JsonResponse
     */
    public static function paginated($data, array $meta): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Data retrieved successfully',
            'data' => $data,
            'pagination' => [
                'current_page' => $meta['current_page'] ?? 1,
                'last_page' => $meta['last_page'] ?? 1,
                'per_page' => $meta['per_page'] ?? 15,
                'total' => $meta['total'] ?? 0,
                'from' => $meta['from'] ?? null,
                'to' => $meta['to'] ?? null,
                'has_more' => ($meta['current_page'] ?? 1) < ($meta['last_page'] ?? 1),
                'has_previous' => ($meta['current_page'] ?? 1) > 1,
            ],
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Not found response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, 404);
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
        return self::error($message, 422, $errors);
    }

    /**
     * Unauthorized response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, 401);
    }

    /**
     * Forbidden response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return self::error($message, 403);
    }

    /**
     * Server error response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return self::error($message, 500);
    }

    /**
     * Too many requests response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function tooManyRequests(string $message = 'Too many requests'): JsonResponse
    {
        return self::error($message, 429);
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
}
