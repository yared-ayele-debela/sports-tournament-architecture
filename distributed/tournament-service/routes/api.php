<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SportController;
use App\Http\Controllers\Api\TournamentController;
use App\Http\Controllers\Api\TournamentSettingsController;
use App\Http\Controllers\Api\VenueController;

/*
|--------------------------------------------------------------------------
| Tournament Service API Routes
|--------------------------------------------------------------------------
|
| All routes require Passport authentication from Auth Service.
| Routes are organized by resource type with proper RESTful conventions.
|
*/

// Public routes for basic tournament information
Route::prefix('tournaments')->group(function () {
    Route::get('/', [TournamentController::class, 'index']);             // GET /api/tournaments
    Route::get('{id}', [TournamentController::class, 'show']);           // GET /api/tournaments/{id}
    Route::get('{id}/matches', [TournamentController::class, 'getTournamentMatches']); // GET /api/tournaments/{id}/matches
    Route::get('{id}/teams', [TournamentController::class, 'getTournamentTeams']); // GET /api/tournaments/{id}/teams
    Route::get('{id}/overview', [TournamentController::class, 'getTournamentOverview']); // GET /api/tournaments/{id}/overview
    Route::get('{id}/statistics', [TournamentController::class, 'getTournamentStatistics']); // GET /api/tournaments/{id}/statistics
    Route::get('{id}/standings', [TournamentController::class, 'getTournamentStandings']); // GET /api/tournaments/{id}/standings
    Route::get('{id}/validate', [TournamentController::class, 'validateTournament']); // GET /api/tournaments/{id}/validate (service-to-service)
});


// Protected routes requiring authentication
Route::middleware(['auth.passport'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Sports Routes
    |--------------------------------------------------------------------------
    |
    | Full CRUD operations for sports management.
    | Admin permissions required for write operations.
    |
    */

    Route::prefix('sports')->group(function () {
        Route::get('/', [SportController::class, 'index']);                    // GET /api/sports
        Route::post('/', [SportController::class, 'store']);                  // POST /api/sports
        Route::get('{id}', [SportController::class, 'show']);                // GET /api/sports/{id}
        Route::put('{id}', [SportController::class, 'update']);              // PUT /api/sports/{id}
        Route::delete('{id}', [SportController::class, 'destroy']);           // DELETE /api/sports/{id}
    });

    /*
    |--------------------------------------------------------------------------
    | Tournaments Routes (Protected)
    |--------------------------------------------------------------------------
    |
    | Write operations for tournaments management.
    | Includes status management and settings.
    | Admin permissions required for write operations.
    |
    */

    Route::prefix('tournaments')->group(function () {
        Route::post('/', [TournamentController::class, 'store']);             // POST /api/tournaments
        Route::put('{id}', [TournamentController::class, 'update']);         // PUT /api/tournaments/{id}
        Route::delete('{id}', [TournamentController::class, 'destroy']);      // DELETE /api/tournaments/{id}
        Route::patch('{id}/status', [TournamentController::class, 'updateStatus']); // PATCH /api/tournaments/{id}/status
    });

    /*
    |--------------------------------------------------------------------------
    | Tournament Settings Routes
    |--------------------------------------------------------------------------
    |
    | Tournament-specific settings management.
    | Admin permissions required for write operations.
    |
    */

    Route::prefix('tournaments')->group(function () {
        Route::get('{id}/settings', [TournamentSettingsController::class, 'show']);     // GET /api/tournaments/{id}/settings
        Route::post('{id}/settings', [TournamentSettingsController::class, 'store']);   // POST /api/tournaments/{id}/settings
    });

    /*
    |--------------------------------------------------------------------------
    | Venues Routes
    |--------------------------------------------------------------------------
    |
    | Full CRUD operations for venues management.
    | Admin permissions required for write operations.
    |
    */

    Route::prefix('venues')->group(function () {
        Route::get('/', [VenueController::class, 'index']);                   // GET /api/venues
        Route::post('/', [VenueController::class, 'store']);                 // POST /api/venues
        Route::get('{id}', [VenueController::class, 'show']);               // GET /api/venues/{id}
        Route::put('{id}', [VenueController::class, 'update']);             // PUT /api/venues/{id}
        Route::delete('{id}', [VenueController::class, 'destroy']);          // DELETE /api/venues/{id}
    });

});

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication Required)
|--------------------------------------------------------------------------
|
| Health check and service information endpoints.
| These routes are accessible without authentication.
|
*/

Route::prefix('health')->group(function () {
    Route::get('/', function () {
        return response()->json([
            'success' => true,
            'message' => 'Tournament Service is healthys',
            'service' => 'tournament-service',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0'
        ]);
    });

    Route::get('/info', function () {
        return response()->json([
            'success' => true,
            'message' => 'Tournament Service Information',
            'service' => 'tournament-service',
            'version' => '1.0.0',
            'environment' => config('app.env'),
            'features' => [
                'sports_management' => true,
                'tournaments_management' => true,
                'venues_management' => true,
                'event_publishing' => true,
                'passport_authentication' => true
            ],
            'endpoints' => [
                'sports' => '/api/sports',
                'tournaments' => '/api/tournaments',
                'venues' => '/api/venues',
                'health' => '/api/health'
            ]
        ]);
    });
});

/*
|--------------------------------------------------------------------------
| API Documentation Route
|--------------------------------------------------------------------------
|
| Redirect root to API documentation or service info.
|
*/

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'Tournament Service APIs',
        'version' => '1.0.0',
        'documentation' => '/api/health/info',
        'endpoints' => [
            'health' => '/api/health',
            'info' => '/api/health/info',
            'sports' => '/api/sports',
            'tournaments' => '/api/tournaments',
            'venues' => '/api/venues'
        ]
    ]);
});
