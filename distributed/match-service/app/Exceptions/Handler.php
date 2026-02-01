<?php

namespace App\Exceptions;

use App\Support\ApiResponse;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @param Throwable $e
     * @return JsonResponse|\Symfony\Component\HttpFoundation\Response
     * @throws Throwable
     */
    public function render($request, Throwable $e)
    {
        // Always return JSON for API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions with standardized format
     *
     * @param Request $request
     * @param Throwable $e
     * @return JsonResponse
     */
    protected function handleApiException(Request $request, Throwable $e): JsonResponse
    {
        // Validation exceptions
        if ($e instanceof ValidationException) {
            return ApiResponse::validationError(
                $e->errors(),
                $e->getMessage() ?: 'Validation failed'
            );
        }

        // HTTP exceptions
        if ($e instanceof HttpException) {
            return $this->handleHttpException($e);
        }

        // Not found exceptions
        if ($e instanceof NotFoundHttpException) {
            return ApiResponse::notFound('The requested resource was not found');
        }

        // Method not allowed
        if ($e instanceof MethodNotAllowedHttpException) {
            return ApiResponse::error(
                'Method not allowed',
                405,
                null,
                'METHOD_NOT_ALLOWED'
            );
        }

        // Unauthorized
        if ($e instanceof UnauthorizedHttpException) {
            return ApiResponse::unauthorized($e->getMessage() ?: 'Unauthorized');
        }

        // Model not found (Laravel)
        if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return ApiResponse::notFound('Resource not found');
        }

        // Service exceptions
        if ($e instanceof ServiceUnavailableException) {
            return ApiResponse::serviceUnavailable(
                $e->getMessage(),
                $e->getServiceName()
            );
        }

        if ($e instanceof ServiceRequestException) {
            $statusCode = $e->getHttpStatusCode() ?? $e->getCode();
            return ApiResponse::error(
                $e->getMessage(),
                $statusCode,
                $e->getContext(),
                $e->getErrorCode()
            );
        }

        if ($e instanceof ServiceException) {
            return ApiResponse::error(
                $e->getMessage(),
                $e->getCode(),
                $e->getContext(),
                $e->getErrorCode()
            );
        }

        // Default server error
        return ApiResponse::serverError(
            config('app.debug') ? $e->getMessage() : 'Internal server error',
            $e
        );
    }

    /**
     * Handle HTTP exceptions
     *
     * @param HttpException $e
     * @return JsonResponse
     */
    protected function handleHttpException(HttpException $e): JsonResponse
    {
        $statusCode = $e->getStatusCode();
        $message = $e->getMessage() ?: 'An error occurred';

        $errorCodes = [
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            405 => 'METHOD_NOT_ALLOWED',
            422 => 'VALIDATION_ERROR',
            429 => 'RATE_LIMIT_EXCEEDED',
            500 => 'INTERNAL_SERVER_ERROR',
            503 => 'SERVICE_UNAVAILABLE',
        ];

        $errorCode = $errorCodes[$statusCode] ?? 'HTTP_ERROR';

        return ApiResponse::error($message, $statusCode, null, $errorCode);
    }
}
