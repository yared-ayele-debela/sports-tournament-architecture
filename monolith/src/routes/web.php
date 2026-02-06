<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\Coach\PlayerController;
use App\Http\Controllers\Admin\Coach\TeamController as CoachTeamController;
use App\Http\Controllers\Admin\SportController;
use App\Http\Controllers\Admin\TournamentController;
use App\Http\Controllers\Admin\TournamentSettingsController;
use App\Http\Controllers\Admin\VenueController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\MatchController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RolePermissionController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Route;

// Admin Routes - Administrator Only
Route::middleware([AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Profile Management (with rate limiting for sensitive operations)
    Route::get('/profile', [AdminProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [AdminProfileController::class, 'update'])
        ->middleware('throttle:admin')
        ->name('profile.update');
    Route::put('/profile/password', [AdminProfileController::class, 'updatePassword'])
        ->middleware('throttle:sensitive')
        ->name('profile.password.update');
    Route::delete('/profile', [AdminProfileController::class, 'destroy'])
        ->middleware('throttle:sensitive')
        ->name('profile.destroy');
    Route::get('/profile/activity', [AdminProfileController::class, 'activity'])->name('profile.activity');

    // Sports Management
    Route::resource('sports', SportController::class);

    // Tournaments Management (with rate limiting for heavy operations)
    Route::resource('tournaments', TournamentController::class);
    Route::post('tournaments/{tournament}/schedule-matches', [TournamentController::class, 'scheduleMatches'])
        ->middleware('throttle:sensitive')
        ->name('tournaments.schedule-matches');
    Route::post('tournaments/{tournament}/recalculate-standings', [TournamentController::class, 'recalculateStandings'])
        ->middleware('throttle:sensitive')
        ->name('tournaments.recalculate-standings');

    // Tournament Settings Management
    Route::resource('tournament-settings', TournamentSettingsController::class);

    // Venues Management
    Route::resource('venues', VenueController::class);

    // Teams Management
    Route::resource('teams', TeamController::class);

    // Matches Management
    Route::resource('matches', MatchController::class);

    // Users Management
    Route::resource('users', UserController::class);

    // Role Permissions Management
    Route::prefix('role-permissions')->name('role-permissions.')->group(function () {
        Route::get('/', [RolePermissionController::class, 'index'])->name('index');
        Route::get('/{role}', [RolePermissionController::class, 'show'])->name('show');
        Route::get('/{role}/edit', [RolePermissionController::class, 'edit'])->name('edit');
        Route::put('/{role}', [RolePermissionController::class, 'update'])->name('update');
    });

    // Coach Routes
    Route::get('/coach-dashboard', [AdminDashboardController::class, 'coachDashboard'])->name('coach-dashboard');
    Route::prefix('coach')->name('coach.')->group(function () {
        Route::resource('teams', CoachTeamController::class);
        // Players Management (Team Scoped)
        Route::get('/teams/{team}/players', [PlayerController::class, 'index'])->name('players.index');
        Route::get('/teams/{team}/players/create', [PlayerController::class, 'create'])->name('players.create');
        Route::post('/teams/{team}/players', [PlayerController::class, 'store'])->name('players.store');
        Route::get('/teams/{team}/players/{player}', [PlayerController::class, 'show'])->name('players.show');
        Route::get('/teams/{team}/players/{player}/edit', [PlayerController::class, 'edit'])->name('players.edit');
        Route::put('/teams/{team}/players/{player}', [PlayerController::class, 'update'])->name('players.update');
        Route::delete('/teams/{team}/players/{player}', [PlayerController::class, 'destroy'])->name('players.destroy');
    });
});

// Referee Routes - Referee Only
require __DIR__.'/referee.php';

// Public Website Routes (No Authentication Required)
require __DIR__.'/public.php';

// Authentication Routes
require __DIR__.'/auth.php';
