<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TournamentApiController;
use App\Http\Controllers\Api\TeamApiController;
use App\Http\Controllers\Api\MatchApiController;
use App\Http\Controllers\Api\StatsApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public API endpoints with rate limiting
Route::prefix('v1')->middleware('throttle:api')->group(function () {

    // Stats endpoints
    Route::get('/stats/dashboard', [StatsApiController::class, 'dashboard']);
    Route::get('/stats/featured-tournaments', [StatsApiController::class, 'featuredTournaments']);
    Route::get('/stats/recent-matches', [StatsApiController::class, 'recentMatches']);
    Route::get('/tournaments/list', [StatsApiController::class, 'tournaments']);
    Route::get('/sports/list', [StatsApiController::class, 'sports']);

    // Tournament endpoints
    Route::get('/tournaments', [TournamentApiController::class, 'index']);
    Route::get('/tournaments/{id}', [TournamentApiController::class, 'show']);
    Route::get('/tournaments/{id}/teams', [TournamentApiController::class, 'teams']);
    Route::get('/tournaments/{id}/matches', [TournamentApiController::class, 'matches']);
    Route::get('/tournaments/{id}/standings', [TournamentApiController::class, 'standings']);

    // Team endpoints
    Route::get('/teams', [TeamApiController::class, 'index']);
    Route::get('/teams/{id}', [TeamApiController::class, 'show']);
    Route::get('/teams/{id}/players', [TeamApiController::class, 'players']);

    // Match endpoints
    Route::get('/matches', [MatchApiController::class, 'index']);
    Route::get('/matches/{id}', [MatchApiController::class, 'show']);
    Route::get('/matches/recent', [MatchApiController::class, 'recent']);
});
