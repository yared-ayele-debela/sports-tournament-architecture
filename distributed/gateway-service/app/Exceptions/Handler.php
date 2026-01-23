<?php

namespace App\Exceptions;

use App\Http\Responses\ApiResponse;
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
     */
    public function render($request, Throwable $exception)
    {
        // Only handle API routes with standardized responses
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $exception);
        }

        return parent::render($request, $exception);
    }

    /**
     * Handle API exceptions with standardized responses.
     */
    protected function handleApiException(Request $request, Throwable $exception): JsonResponse
    {
        // Validation exceptions
        if ($exception instanceof ValidationException) {
            return ApiResponse::validationError($exception->errors());
        }

        // HTTP exceptions
        if ($exception instanceof NotFoundHttpException) {
            return ApiResponse::notFound('Resource not found');
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return ApiResponse::error('Method not allowed', 405);
        }

        if ($exception instanceof UnauthorizedHttpException) {
            return ApiResponse::unauthorized('Unauthorized');
        }

        if ($exception instanceof HttpException) {
            return ApiResponse::error($exception->getMessage(), $exception->getStatusCode());
        }

        // Default server error
        return ApiResponse::serverError('Internal server error');
    }
}
