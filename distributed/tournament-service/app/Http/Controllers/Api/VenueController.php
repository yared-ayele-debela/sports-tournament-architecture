<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Services\HttpClients\AuthServiceClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class VenueController extends Controller
{
    protected AuthServiceClient $authClient;

    /**
     * Create a new VenueController instance.
     */
    public function __construct(AuthServiceClient $authClient)
    {
        $this->authClient = $authClient;
    }

    /**
     * Display a listing of venues.
     */
    public function index(): JsonResponse
    {
        try {
            $venues = Venue::withCount('matches')->orderBy('name')->paginate(20);

            return response()->json([
                'success' => true,
                'message' => 'Venues retrieved successfully',
                'data' => $venues
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving venues', [
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
            // Check if user has admin permissions
            $userId = $request->user()?->id;
            if (!$userId || !$this->authClient->userHasPermission($userId, 'manage_venues')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.',
                    'error' => 'Insufficient permissions'
                ], 403);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'location' => 'required|string|max:500',
                'capacity' => 'required|integer|min:1|max:1000000'
            ]);

            $venue = Venue::create($validated);

            Log::info('Venue created successfully', [
                'venue_id' => $venue->id,
                'name' => $venue->name,
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Venue created successfully',
                'data' => $venue
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating venue', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId ?? null
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
            $venue = Venue::withCount('matches')->find($id);

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
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving venue', [
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
            // Check if user has admin permissions
            $userId = $request->user()?->id;
            if (!$userId || !$this->authClient->userHasPermission($userId, 'manage_venues')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.',
                    'error' => 'Insufficient permissions'
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
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('venues')->ignore($venue->id)
                ],
                'location' => 'required|string|max:500',
                'capacity' => 'required|integer|min:1|max:1000000'
            ]);

            $venue->update($validated);

            Log::info('Venue updated successfully', [
                'venue_id' => $venue->id,
                'name' => $venue->name,
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Venue updated successfully',
                'data' => $venue
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating venue', [
                'venue_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId ?? null
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
    public function destroy(string $id): JsonResponse
    {
        try {
            // Check if user has admin permissions
            $userId = request()->user()?->id;
            if (!$userId || !$this->authClient->userHasPermission($userId, 'manage_venues')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.',
                    'error' => 'Insufficient permissions'
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

            // Check if venue has associated matches (external reference)
            // This would need to be implemented via API call to Match Service
            // For now, we'll allow deletion with a warning

            $venue->delete();

            Log::info('Venue deleted successfully', [
                'venue_id' => $venue->id,
                'name' => $venue->name,
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Venue deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting venue', [
                'venue_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete venue',
                'error' => 'Internal server error'
            ], 500);
        }
    }
}
