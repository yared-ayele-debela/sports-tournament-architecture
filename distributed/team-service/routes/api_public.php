<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Public\PublicTeamController;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
|
| Public endpoints accessible without authentication.
| All routes are prefixed with /api/public
| Middleware applied: rate limiting, CORS, JSON response
|
*/

Route::prefix('public')->group(function () {

    // Apply public API middleware to all routes in this group
    Route::middleware(['force.json', 'public.cors', 'public.rate.limit'])->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Team Public Routes
        |--------------------------------------------------------------------------
        |
        | Public team information endpoints.
        | No authentication required.
        |
        */

        // Tournament teams
        Route::prefix('tournaments')->group(function () {
            Route::get('{tournamentId}/teams', [PublicTeamController::class, 'tournamentTeams'])
                ->name('public.tournaments.teams')
                ->where('tournamentId', '[0-9]+');
        });

        // Team details and related data
        Route::prefix('teams')->group(function () {
            // Get team details
            Route::get('{id}', [PublicTeamController::class, 'show'])
                ->name('public.teams.show')
                ->where('id', '[0-9]+');

            // Get team players
            Route::get('{id}/players', [PublicTeamController::class, 'players'])
                ->name('public.teams.players')
                ->where('id', '[0-9]+');

            // Get team matches
            Route::get('{id}/matches', [PublicTeamController::class, 'matches'])
                ->name('public.teams.matches')
                ->where('id', '[0-9]+');
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
