<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
// use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(\Laravel\Passport\Http\Middleware\CreateFreshApiToken::class);
        $middleware->alias([
            'auth.api' => \Laravel\Passport\Http\Middleware\CheckClientCredentials::class,
            'auth.passport' => \Laravel\Passport\Http\Middleware\CheckClientCredentials::class,
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
