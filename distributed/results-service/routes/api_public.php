<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Public\PublicStandingsController;
use App\Http\Controllers\Api\Public\PublicApiDocumentationController;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
|
| These routes are publicly accessible without authentication.
| They include rate limiting, CORS headers, and heavy caching.
|
*/

Route::prefix('public')->group(function () {
    Route::middleware(['force.json', 'public.cors', 'public.rate.limit'])->group(function () {
        // Tournament standings
        Route::prefix('tournaments/{tournamentId}')->group(function () {
            Route::get('standings', [PublicStandingsController::class, 'standings'])
                ->name('public.tournaments.standings.index')
                ->where('tournamentId', '[0-9]+');

            Route::get('statistics', [PublicStandingsController::class, 'statistics'])
                ->name('public.tournaments.statistics.index')
                ->where('tournamentId', '[0-9]+');

            Route::get('top-scorers', [PublicStandingsController::class, 'topScorers'])
                ->name('public.tournaments.top-scorers.index')
                ->where('tournamentId', '[0-9]+');
        });

        // Team standing
        Route::prefix('teams')->group(function () {
            Route::get('{teamId}/standing', [PublicStandingsController::class, 'teamStanding'])
                ->name('public.teams.standing.show')
                ->where('teamId', '[0-9]+');
        });

        /*
        |--------------------------------------------------------------------------
        | API Documentation
        |--------------------------------------------------------------------------
        |
        | Self-documenting API endpoint.
        |
        */

        Route::get('/docs', [PublicApiDocumentationController::class, 'index'])
            ->name('public.docs');

        // CORS preflight
        Route::options('{any}', function () {
            return response('', 200);
        })->where('any', '.*')->name('public.options');
    });
});
