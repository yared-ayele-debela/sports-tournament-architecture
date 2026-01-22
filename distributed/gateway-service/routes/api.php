<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Public\PublicHomeController;
use App\Http\Controllers\Public\PublicTournamentController;
use App\Http\Controllers\Public\PublicMatchController;
use App\Http\Controllers\Public\PublicStandingsController;
use App\Http\Controllers\Public\PublicTeamController;
use App\Http\Controllers\Public\PublicSearchController;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public API Routes with /api/public prefix
Route::prefix('public')->group(function () {
    
    // Homepage Routes
    Route::get('/featured', [PublicHomeController::class, 'index'])->middleware('public-api');
    Route::get('/tournaments/upcoming', [PublicHomeController::class, 'stats'])->middleware('public-api');
    
    // Tournament Routes
    Route::prefix('tournaments')->middleware('public-api')->group(function () {
        Route::get('/', [PublicTournamentController::class, 'index']);
        Route::get('/{id}', [PublicTournamentController::class, 'show']);
        Route::get('/{id}/standings', [PublicStandingsController::class, 'show']);
        Route::get('/{id}/matches', [PublicTournamentController::class, 'matches']);
        Route::get('/{id}/statistics', [PublicStandingsController::class, 'statistics']);
        Route::get('/{id}/overview', [PublicTournamentController::class, 'overview']);
        Route::get('/{id}/teams', [PublicTournamentController::class, 'teams']);
        Route::get('/{id}/top-scorers', [PublicStandingsController::class, 'topScorers']);
    });
    
    // Match Routes
    Route::prefix('matches')->group(function () {
        Route::get('/', [PublicMatchController::class, 'index'])->middleware('public-api');
        Route::get('/live', [PublicMatchController::class, 'live'])->middleware('live-matches');
        Route::get('/upcoming', [PublicMatchController::class, 'upcoming'])->middleware('public-api');
        Route::get('/completed', [PublicMatchController::class, 'completed'])->middleware('public-api');
        Route::get('/date/{date}', [PublicMatchController::class, 'byDate'])->middleware('public-api');
        Route::get('/{id}', [PublicMatchController::class, 'show'])->middleware('public-api');
        Route::get('/{id}/events', [PublicMatchController::class, 'events'])->middleware('public-api');
    });
    
    // Team Routes
    Route::prefix('teams')->middleware('public-api')->group(function () {
        Route::get('/{id}', [PublicTeamController::class, 'show']);
        Route::get('/{id}/overview', [PublicTeamController::class, 'overview']);
        Route::get('/{id}/squad', [PublicTeamController::class, 'squad']);
        Route::get('/{id}/matches', [PublicTeamController::class, 'matches']);
        Route::get('/{id}/statistics', [PublicTeamController::class, 'statistics']);
    });
    
    // Search Routes
    Route::prefix('search')->group(function () {
        Route::get('/', [PublicSearchController::class, 'search'])->middleware('public-search');
    });
    
    // Standings Routes (Alternative paths)
    Route::prefix('standings')->middleware('public-api')->group(function () {
        Route::get('/tournament/{tournamentId}', [PublicStandingsController::class, 'show']);
        Route::get('/tournament/{tournamentId}/with-teams', [PublicStandingsController::class, 'withTeams']);
        Route::get('/tournament/{tournamentId}/statistics', [PublicStandingsController::class, 'statistics']);
        Route::get('/tournament/{tournamentId}/top-scorers', [PublicStandingsController::class, 'topScorers']);
    });
});

// Health Check Route
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'gateway-service',
        'timestamp' => now()->toISOString(),
    ]);
})->middleware('public-api');

// API Documentation Route
Route::get('/docs', function () {
    return response()->json([
        'name' => 'Sports Tournament Gateway API',
        'version' => '1.0.0',
        'description' => 'Public API for sports tournament data aggregation',
        'endpoints' => [
            'Homepage' => [
                'GET /api/public/featured' => 'Get featured tournaments',
                'GET /api/public/tournaments/upcoming' => 'Get upcoming tournaments',
            ],
            'Tournaments' => [
                'GET /api/public/tournaments' => 'List tournaments',
                'GET /api/public/tournaments/{id}' => 'Get tournament details',
                'GET /api/public/tournaments/{id}/standings' => 'Get tournament standings',
                'GET /api/public/tournaments/{id}/matches' => 'Get tournament matches',
                'GET /api/public/tournaments/{id}/statistics' => 'Get tournament statistics',
            ],
            'Matches' => [
                'GET /api/public/matches' => 'List matches',
                'GET /api/public/matches/live' => 'Get live matches',
                'GET /api/public/matches/{id}' => 'Get match details',
                'GET /api/public/matches/{id}/events' => 'Get match events',
            ],
            'Teams' => [
                'GET /api/public/teams/{id}' => 'Get team profile',
                'GET /api/public/teams/{id}/matches' => 'Get team matches',
                'GET /api/public/teams/{id}/statistics' => 'Get team statistics',
            ],
            'Search' => [
                'GET /api/public/search?q=' => 'Global search',
                'GET /api/public/search/suggestions?q=' => 'Search suggestions',
            ],
        ],
        'rate_limits' => [
            'public-api' => '60 requests per minute per IP',
            'public-search' => '20 requests per minute per IP',
            'live-matches' => '120 requests per minute per IP',
        ],
    ]);
})->middleware('public-api');
