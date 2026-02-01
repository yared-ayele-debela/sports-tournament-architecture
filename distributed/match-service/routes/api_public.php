<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Public\PublicMatchController;

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
        // Live matches
        Route::get('matches/live', [PublicMatchController::class, 'live'])->name('public.matches.live');

        // Tournament matches
        Route::prefix('tournaments/{tournamentId}')->group(function () {
            Route::get('matches', [PublicMatchController::class, 'tournamentMatches'])->name('public.tournaments.matches.index')->where('tournamentId', '[0-9]+');
        });

        // Match details
        Route::prefix('matches')->group(function () {
            Route::get('{id}', [PublicMatchController::class, 'show'])->name('public.matches.show')->where('id', '[0-9]+');
            Route::get('{id}/events', [PublicMatchController::class, 'events'])->name('public.matches.events.index')->where('id', '[0-9]+');
            Route::get('today', [PublicMatchController::class, 'today'])->name('public.matches.today');
            Route::get('upcoming', [PublicMatchController::class, 'upcoming'])->name('public.matches.upcoming');
        });

        // CORS preflight
        Route::options('{any}', function () {
            return response('', 200);
        })->where('any', '.*')->name('public.options');
    });
});
