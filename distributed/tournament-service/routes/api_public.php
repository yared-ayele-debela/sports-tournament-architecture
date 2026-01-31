<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Public\PublicTournamentController;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
|
| Public endpoints accessible without authentication.
| All routes are prefixed with /api/public
| Middleware applied: rate limiting, CORS, JSON response
|
| These routes are optimized for public consumption with:
| - Heavy caching
| - Rate limiting (100 requests/minute per IP)
| - CORS headers for cross-origin access
| - Consistent JSON response format
|
*/

Route::prefix('public')->group(function () {

    // Apply public API middleware to all routes in this group
    Route::middleware(['force.json', 'public.cors', 'public.rate.limit'])->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Tournament Public Routes
        |--------------------------------------------------------------------------
        |
        | Public tournament information endpoints.
        | No authentication required.
        |
        */

        Route::prefix('tournaments')->group(function () {
            // List tournaments (with filters)
            Route::get('/', [PublicTournamentController::class, 'index'])
                ->name('public.tournaments.index');

            // Get featured tournaments
            Route::get('/featured', [PublicTournamentController::class, 'featured'])
                ->name('public.tournaments.featured');

            // Get upcoming tournaments
            Route::get('/upcoming', [PublicTournamentController::class, 'upcoming'])
                ->name('public.tournaments.upcoming');

            // Get tournament details
            Route::get('{id}', [PublicTournamentController::class, 'show'])
                ->name('public.tournaments.show')
                ->where('id', '[0-9]+');
        });

        /*
        |--------------------------------------------------------------------------
        | Sports Public Routes
        |--------------------------------------------------------------------------
        |
        | Public sports information endpoints.
        |
        */

        Route::prefix('sports')->group(function () {
            // List all sports with tournament counts
            Route::get('/', [PublicTournamentController::class, 'sports'])
                ->name('public.sports.index');
        });

        /*
        |--------------------------------------------------------------------------
        | Venues Public Routes
        |--------------------------------------------------------------------------
        |
        | Public venue information endpoints.
        |
        */

        Route::prefix('venues')->group(function () {
            // List all venues
            Route::get('/', [PublicTournamentController::class, 'venues'])
                ->name('public.venues.index');
        });

        /*
        |--------------------------------------------------------------------------
        | CORS Preflight Handler
        |--------------------------------------------------------------------------
        |
        | Handle OPTIONS requests for CORS preflight.
        |
        */

        Route::options('{any}', function () {
            return response('', 200);
        })->where('any', '.*')->name('public.options');

    });
});
