<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
// use Throwable;

use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Load public API routes
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api_public.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Add correlation ID middleware to all API routes
        $middleware->api(prepend: [
            \App\Http\Middleware\CorrelationIdMiddleware::class,
        ]);

        $middleware->api(\Laravel\Passport\Http\Middleware\CreateFreshApiToken::class);
        $middleware->alias([
            'auth.api' => \Laravel\Passport\Http\Middleware\CheckClientCredentials::class,
            'auth.passport' => \Laravel\Passport\Http\Middleware\CheckClientCredentials::class,
            'public.rate.limit' => \App\Http\Middleware\PublicRateLimitMiddleware::class,
            'public.cors' => \App\Http\Middleware\PublicCorsMiddleware::class,
            'force.json' => \App\Http\Middleware\ForceJsonResponseMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Use custom exception handler for standardized error responses
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return app(\App\Exceptions\Handler::class)->render($request, $e);
            }
        });
    })->create();
