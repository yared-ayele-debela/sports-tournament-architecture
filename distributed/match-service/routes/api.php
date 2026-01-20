<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MatchController;
use App\Http\Controllers\Api\MatchEventController;
use App\Http\Controllers\Api\MatchReportController;

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
        'message' => 'Match Service is running',
        'service' => 'match-service',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString()
    ]);
});

Route::middleware([\App\Http\Middleware\ValidateUserServiceToken::class])->group(function () {
    
    // Matches Routes
    Route::get('/tournaments/{tournamentId}/matches', [MatchController::class, 'index']);
    Route::post('/matches', [MatchController::class, 'store']);
    Route::get('/matches/{id}', [MatchController::class, 'show']);
    Route::put('/matches/{id}', [MatchController::class, 'update']);
    Route::delete('/matches/{id}', [MatchController::class, 'destroy']);
    Route::patch('/matches/{id}/status', [MatchController::class, 'updateStatus']);
    Route::post('/tournaments/{tournamentId}/generate-schedule', [MatchController::class, 'generateSchedule']);
    
    // Match Events Routes
    Route::get('/matches/{matchId}/events', [MatchEventController::class, 'index']);
    Route::post('/matches/{matchId}/events', [MatchEventController::class, 'store']);
    Route::delete('/events/{id}', [MatchEventController::class, 'destroy']);
    
    // Match Reports Routes
    Route::get('/matches/{matchId}/report', [MatchReportController::class, 'show']);
    Route::post('/matches/{matchId}/report', [MatchReportController::class, 'store']);
});
