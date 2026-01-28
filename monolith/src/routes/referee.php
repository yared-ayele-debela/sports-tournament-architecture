<?php

use App\Http\Controllers\Admin\Referee\MatchController;
use App\Http\Controllers\Admin\Referee\MatchEventController;
use Illuminate\Support\Facades\Route;

// Referee Routes - Referee Only
Route::middleware(['auth', 'role:referee'])->prefix('admin/referee')->name('admin.referee.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [MatchController::class, 'dashboard'])->name('dashboard');
    
    // Match Management
    Route::get('/matches', [MatchController::class, 'index'])->name('matches.index');
    Route::get('/matches/{match}', [MatchController::class, 'show'])->name('matches.show');
    Route::post('/matches/{match}/start', [MatchController::class, 'start'])->name('matches.start');
    Route::post('/matches/{match}/pause', [MatchController::class, 'pause'])->name('matches.pause');
    Route::post('/matches/{match}/end', [MatchController::class, 'end'])->name('matches.end');
    Route::post('/matches/{match}/update-score', [MatchController::class, 'updateScore'])->name('matches.update-score');
    Route::post('/matches/{match}/update-minute', [MatchController::class, 'updateMinute'])->name('matches.update-minute');
    
    // Event Management
    Route::post('/matches/{match}/events', [MatchEventController::class, 'store'])->name('events.store');
    Route::put('/matches/{match}/events/{event}', [MatchEventController::class, 'update'])->name('events.update');
    Route::delete('/matches/{match}/events/{event}', [MatchEventController::class, 'destroy'])->name('events.destroy');
    Route::get('/matches/{match}/events', [MatchEventController::class, 'index'])->name('events.index');
});
