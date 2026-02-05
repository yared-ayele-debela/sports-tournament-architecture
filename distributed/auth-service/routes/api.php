<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserServiceController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\PermissionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Authentication Service (v1)
|--------------------------------------------------------------------------
*/
    /*
    |--------------------------------------------------------------------------
    | Public Authentication Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    /*
    |--------------------------------------------------------------------------
    | Protected Authentication Routes (Passport required)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:api')->prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
    });

    /*
    |--------------------------------------------------------------------------
    | Internal User Service Routes (Passport required)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:api')->prefix('users')->group(function () {
        Route::get('{id}', [UserServiceController::class, 'getUserById']);
        Route::get('{id}/validate', [UserServiceController::class, 'validateUserById']);
        Route::post('{id}/roles', [UserServiceController::class, 'assignRole']);
        Route::get('{id}/permissions', [UserServiceController::class, 'getUserPermissions']);
        Route::post('validate', [UserServiceController::class, 'validateUser']);
    });

    /*
    |--------------------------------------------------------------------------
    | User Management CRUD Routes (Passport required)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:api')->prefix('admin/users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('{id}', [UserController::class, 'show']);
        Route::put('{id}', [UserController::class, 'update']);
        Route::patch('{id}', [UserController::class, 'update']);
        Route::delete('{id}', [UserController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Role Management CRUD Routes (Passport required)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:api')->prefix('admin/roles')->group(function () {
        Route::get('/', [RoleController::class, 'index']);
        Route::post('/', [RoleController::class, 'store']);
        Route::get('{id}', [RoleController::class, 'show']);
        Route::put('{id}', [RoleController::class, 'update']);
        Route::patch('{id}', [RoleController::class, 'update']);
        Route::delete('{id}', [RoleController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Permission Management CRUD Routes (Passport required)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:api')->prefix('admin/permissions')->group(function () {
        Route::get('/', [PermissionController::class, 'index']);
        Route::post('/', [PermissionController::class, 'store']);
        Route::get('{id}', [PermissionController::class, 'show']);
        Route::put('{id}', [PermissionController::class, 'update']);
        Route::patch('{id}', [PermissionController::class, 'update']);
        Route::delete('{id}', [PermissionController::class, 'destroy']);
    });

    /*
    |--------------------------------------------------------------------------
    | Health Check (Public)
    |--------------------------------------------------------------------------
    */
    Route::get('health', function () {
        return response()->json([
            'success' => true,
            'message' => 'Authentication Service is running',
            'service' => 'auth-service',
            'version' => '1.0.0',
            'timestamp' => now()->toISOString()
        ]);
    });

    /*
    |--------------------------------------------------------------------------
    | Service Info (Public)
    |--------------------------------------------------------------------------
    */
 

