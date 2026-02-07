<?php

use App\Http\Controllers\Admin\Referee\MatchController;
use App\Http\Controllers\Admin\Referee\MatchEventController;
use App\Http\Controllers\Admin\Referee\MatchReportController;
use Illuminate\Support\Facades\Route;

// Referee Routes - Referee Only
Route::middleware(['auth', 'role:referee'])->prefix('admin/referee')->name('admin.referee.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [MatchController::class, 'dashboard'])->name('dashboard');

    // Match Management (with rate limiting for state-changing operations)
    Route::get('/matches', [MatchController::class, 'index'])->name('matches.index');
    Route::get('/matches/{match}', [MatchController::class, 'show'])->name('matches.show');
    Route::post('/matches/{match}/start', [MatchController::class, 'start'])
        ->middleware('throttle:admin')
        ->name('matches.start');
    Route::post('/matches/{match}/pause', [MatchController::class, 'pause'])
        ->middleware('throttle:admin')
        ->name('matches.pause');
    Route::post('/matches/{match}/resume', [MatchController::class, 'resume'])
        ->middleware('throttle:admin')
        ->name('matches.resume');
    Route::post('/matches/{match}/end', [MatchController::class, 'end'])
        ->middleware('throttle:admin')
        ->name('matches.end');
    Route::post('/matches/{match}/update-score', [MatchController::class, 'updateScore'])
        ->middleware('throttle:admin')
        ->name('matches.update-score');
    Route::post('/matches/{match}/update-minute', [MatchController::class, 'updateMinute'])
        ->middleware('throttle:admin')
        ->name('matches.update-minute');

    // Event Management (with rate limiting)
    Route::post('/matches/{match}/events', [MatchEventController::class, 'store'])
        ->middleware('throttle:admin')
        ->name('events.store');
    Route::put('/matches/{match}/events/{event}', [MatchEventController::class, 'update'])
        ->middleware('throttle:admin')
        ->name('events.update');
    Route::delete('/matches/{match}/events/{event}', [MatchEventController::class, 'destroy'])
        ->middleware('throttle:admin')
        ->name('events.destroy');
    Route::get('/matches/{match}/events', [MatchEventController::class, 'index'])->name('events.index');

    // Match Report Management (with rate limiting for sensitive operations)
    Route::get('/matches/{match}/reports/create', [MatchReportController::class, 'create'])->name('reports.create');
    Route::post('/matches/{match}/reports', [MatchReportController::class, 'store'])
        ->middleware('throttle:sensitive')
        ->name('reports.store');
    Route::get('/matches/{match}/reports/edit', [MatchReportController::class, 'edit'])->name('reports.edit');
    Route::put('/matches/{match}/reports', [MatchReportController::class, 'update'])
        ->middleware('throttle:sensitive')
        ->name('reports.update');
    Route::get('/matches/{match}/reports', [MatchReportController::class, 'show'])->name('reports.show');
});
