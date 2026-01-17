<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserServiceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Authentication Service
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes (no authentication required)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Protected routes (JWT authentication required)
Route::middleware('auth:api')->prefix('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);
});

// Internal routes (service-to-service communication with JWT auth)
Route::middleware('auth:api')->prefix('users')->group(function () {
    Route::get('{id}', [UserServiceController::class, 'getUserById']);
    Route::post('{id}/roles', [UserServiceController::class, 'assignRole']);
    Route::get('{id}/permissions', [UserServiceController::class, 'getUserPermissions']);
});

// User validation route (service-to-service)
Route::middleware('auth:api')->post('users/validate', [UserServiceController::class, 'validateUser']);

// Health check endpoint (no auth required)
Route::get('health', function () {
    return response()->json([
        'success' => true,
        'message' => 'Authentication Service is running',
        'service' => 'auth-service',
        'version' => '1.0.0',
        'timestamp' => now()->toISOString()
    ]);
});

// Service info endpoint (no auth required)
Route::get('info', function () {
    return response()->json([
        'success' => true,
        'service' => 'auth-service',
        'description' => 'Authentication and Authorization Service',
        'version' => '1.0.0',
        'endpoints' => [
            'public' => [
                'POST /api/auth/register' => 'Register new user',
                'POST /api/auth/login' => 'User login',
                'GET /api/health' => 'Health check',
                'GET /api/info' => 'Service information'
            ],
            'protected' => [
                'POST /api/auth/logout' => 'User logout',
                'POST /api/auth/refresh' => 'Refresh JWT token',
                'GET /api/auth/me' => 'Get authenticated user profile'
            ],
            'internal' => [
                'GET /api/users/{id}' => 'Get user details',
                'POST /api/users/{id}/roles' => 'Assign role to user',
                'GET /api/users/{id}/permissions' => 'Get user permissions',
                'POST /api/users/validate' => 'Validate user existence'
            ]
        ]
    ]);
});
