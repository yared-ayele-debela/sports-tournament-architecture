<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VenueController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    /**
     * Display a listing of venues.
     */
    public function index(): JsonResponse
    {
        try {
            $venues = Venue::all();

            return response()->json([
                'success' => true,
                'message' => 'Venues retrieved successfully',
                'data' => $venues
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve venues', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve venues',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created venue (Admin only).
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
            
            // Check if user has admin role OR manage_venues permission
            $isAdmin = collect($userRoles)->contains('name', 'Administrator');
            $canManageVenues = $this->authService->userHasPermission(['data' => ['permissions' => $userPermissions]], 'manage_venues');
            
            if (!$isAdmin && !$canManageVenues) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Venue management access required.',
                    'error' => 'Insufficient permissions',
                    'user_roles' => $userRoles,
                    'user_permissions' => $userPermissions
                ], 403);
            }
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'location' => 'nullable|string|max:500',
                'capacity' => 'nullable|integer|min:1'
            ]);

            $venue = Venue::create($validated);

            Log::info('Venue created successfully', [
                'venue_id' => $venue->id,
                'name' => $venue->name,
                'user_id' => $user['id']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Venue created successfully',
                'data' => $venue
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create venue', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create venue',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified venue.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $venue = Venue::find($id);

            if (!$venue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Venue not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Venue retrieved successfully',
                'data' => $venue
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve venue', [
                'venue_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve venue',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update the specified venue (Admin only).
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
            
            // Check if user has admin role OR manage_venues permission
            $isAdmin = collect($userRoles)->contains('name', 'Administrator');
            $canManageVenues = $this->authService->userHasPermission(['data' => ['permissions' => $userPermissions]], 'manage_venues');
            
            if (!$isAdmin && !$canManageVenues) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Venue management access required.',
                    'error' => 'Insufficient permissions',
                    'user_roles' => $userRoles,
                    'user_permissions' => $userPermissions
                ], 403);
            }
            $venue = Venue::find($id);

            if (!$venue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Venue not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'location' => 'nullable|string|max:500',
                'capacity' => 'nullable|integer|min:1'
            ]);

            $venue->update($validated);

            Log::info('Venue updated successfully', [
                'venue_id' => $venue->id,
                'name' => $venue->name,
                'user_id' => $user['id']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Venue updated successfully',
                'data' => $venue
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update venue', [
                'venue_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update venue',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified venue (Admin only).
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
            
            // Check if user has admin role OR manage_venues permission
            $isAdmin = collect($userRoles)->contains('name', 'Administrator');
            $canManageVenues = $this->authService->userHasPermission(['data' => ['permissions' => $userPermissions]], 'manage_venues');
            
            if (!$isAdmin && !$canManageVenues) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Venue management access required.',
                    'error' => 'Insufficient permissions',
                    'user_roles' => $userRoles,
                    'user_permissions' => $userPermissions
                ], 403);
            }
            $venue = Venue::find($id);

            if (!$venue) {
                return response()->json([
                    'success' => false,
                    'message' => 'Venue not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            $venue->delete();

            Log::info('Venue deleted successfully', [
                'venue_id' => $id,
                'venue_name' => $venue->name,
                'user_id' => $user['id']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Venue deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete venue', [
                'venue_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete venue',
                'error' => 'Internal server error'
            ], 500);
        }
    }
}
