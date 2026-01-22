<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sport;
use App\Services\AuthService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SportController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Display a listing of sports.
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->query('per_page', 20);
            $perPage = max(1, min(100, $perPage));

            $sports = Sport::orderByDesc('id')->paginate($perPage);

            return ApiResponse::paginated($sports, 'Sports retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve sports', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve sports',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created sport (Admin only).
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Get authenticated user from middleware
            $user = $request->get('authenticated_user');
            $userRoles = $request->get('user_roles', []);
            $userPermissions = $request->get('user_permissions', []);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. User not authenticated.',
                    'error' => 'No authenticated user'
                ], 401);
            }
            
            // Check if user has admin role OR manage_sports permission
            $isAdmin = collect($userRoles)->contains('name', 'Administrator');
            $canManageSports = $this->authService->userHasPermission(['data' => ['permissions' => $userPermissions]], 'manage_sports');
            
            if (!$isAdmin && !$canManageSports) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Sports management access required.',
                    'error' => 'Insufficient permissions',
                    'user_roles' => $userRoles,
                    'user_permissions' => $userPermissions
                ], 403);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:sports',
                'team_based' => 'required|boolean',
                'rules' => 'nullable|string',
                'description' => 'nullable|string|max:1000'
            ]);

            $sport = Sport::create($validated);

            Log::info('Sport created successfully', [
                'sport_id' => $sport->id,
                'name' => $sport->name,
                'user_id' => $user['id']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sport created successfully',
                'data' => $sport
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create sport', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create sport',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified sport.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $sport = Sport::find($id);

            if (!$sport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sport not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Sport retrieved successfully',
                'data' => $sport
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve sport', [
                'sport_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve sport',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update the specified sport (Admin only).
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            // Get authenticated user from middleware
            $user = $request->get('authenticated_user');
            $userRoles = $request->get('user_roles', []);
            $userPermissions = $request->get('user_permissions', []);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. User not authenticated.',
                    'error' => 'No authenticated user'
                ], 401);
            }
            
            // Check if user has admin role OR manage_sports permission
            $isAdmin = collect($userRoles)->contains('name', 'Administrator');
            $canManageSports = $this->authService->userHasPermission(['data' => ['permissions' => $userPermissions]], 'manage_sports');
            
            if (!$isAdmin && !$canManageSports) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Sports management access required.',
                    'error' => 'Insufficient permissions',
                    'user_roles' => $userRoles,
                    'user_permissions' => $userPermissions
                ], 403);
            }

            $sport = Sport::find($id);

            if (!$sport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sport not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255|unique:sports,name,' . $id,
                'team_based' => 'sometimes|boolean',
                'rules' => 'nullable|string',
                'description' => 'nullable|string|max:1000'
            ]);

            $sport->update($validated);

            Log::info('Sport updated successfully', [
                'sport_id' => $sport->id,
                'name' => $sport->name,
                'user_id' => $user['id']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sport updated successfully',
                'data' => $sport
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update sport', [
                'sport_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update sport',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified sport (Admin only).
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            // Get authenticated user from middleware
            $user = $request->get('authenticated_user');
            $userRoles = $request->get('user_roles', []);
            $userPermissions = $request->get('user_permissions', []);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. User not authenticated.',
                    'error' => 'No authenticated user'
                ], 401);
            }
            
            // Check if user has admin role OR manage_sports permission
            $isAdmin = collect($userRoles)->contains('name', 'Administrator');
            $canManageSports = $this->authService->userHasPermission(['data' => ['permissions' => $userPermissions]], 'manage_sports');
            
            if (!$isAdmin && !$canManageSports) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Sports management access required.',
                    'error' => 'Insufficient permissions',
                    'user_roles' => $userRoles,
                    'user_permissions' => $userPermissions
                ], 403);
            }

            $sport = Sport::find($id);

            if (!$sport) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sport not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            $sport->delete();

            Log::info('Sport deleted successfully', [
                'sport_id' => $id,
                'sport_name' => $sport->name,
                'user_id' => $user['id']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sport deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete sport', [
                'sport_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete sport',
                'error' => 'Internal server error'
            ], 500);
        }
    }
}
