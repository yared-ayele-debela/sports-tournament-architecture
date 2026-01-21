<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StandingsController;
use App\Http\Controllers\Api\MatchResultController;
use App\Http\Controllers\Api\StatisticsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*
|--------------------------------------------------------------------------
| Health Check (Public)
|--------------------------------------------------------------------------
*/
Route::get('health', function () {
    return response()->json([
        'success' => true,
        'message' => 'Results Service is running',
        'service' => 'results-service',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString()
    ]);
});

Route::middleware([\App\Http\Middleware\ValidateUserServiceToken::class])->group(function () {
    
    // Standings Routes
    Route::get('/tournaments/{tournamentId}/standings', [StandingsController::class, 'index']);
    Route::post('/standings/recalculate/{tournamentId}', [StandingsController::class, 'recalculate']);
    
    // Match Results Routes
    Route::get('/tournaments/{tournamentId}/results', [MatchResultController::class, 'index']);
    Route::get('/results/{id}', [MatchResultController::class, 'show']);
    Route::post('/matches/{matchId}/finalize', [MatchResultController::class, 'finalize']);
    
    // Statistics Routes
    Route::get('/teams/{teamId}/statistics', [StatisticsController::class, 'teamStatistics']);
    Route::get('/tournaments/{tournamentId}/statistics', [StatisticsController::class, 'tournamentStatistics']);
    
});
