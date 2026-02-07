<?php

use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\MatchController;
use App\Http\Controllers\Public\TeamController;
use App\Http\Controllers\Public\TournamentController;
use Illuminate\Support\Facades\Route;

// Public Website Routes (No Authentication Required)
Route::get('/', [HomeController::class, 'index'])->name('home');

// Tournament Routes
Route::get('/tournaments', [TournamentController::class, 'index'])->name('tournaments.index');
Route::get('/tournaments/{tournament}', [TournamentController::class, 'show'])->name('tournaments.show');

// Team Routes
Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show');

// Match Routes
Route::get('/matches', [MatchController::class, 'index'])->name('matches.index');
Route::get('/matches/{match}', [MatchController::class, 'show'])->name('matches.show');
Route::get('/matches/{match}/live', [MatchController::class, 'live'])->name('matches.live');

// Tournament-specific routes (nested)
Route::get('/tournaments/{tournament}/matches', [TournamentController::class, 'matches'])->name('tournaments.matches');
Route::get('/tournaments/{tournament}/standings', [TournamentController::class, 'standings'])->name('tournaments.standings');
Route::get('/tournaments/{tournament}/teams', [TournamentController::class, 'teams'])->name('tournaments.teams');
