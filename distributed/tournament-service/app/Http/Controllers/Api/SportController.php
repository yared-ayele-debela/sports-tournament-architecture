<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sport;
use App\Services\HttpClients\AuthServiceClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SportController extends Controller
{
    protected AuthServiceClient $authClient;

    /**
     * Create a new SportController instance.
     */
    public function __construct(AuthServiceClient $authClient)
    {
        $this->authClient = $authClient;
    }

    /**
     * Display a listing of all sports.
     */
    public function index(): JsonResponse
    {
        try {
            $sports = Sport::withCount('tournaments')->get();

            return response()->json([
                'success' => true,
                'message' => 'Sports retrieved successfully',
                'data' => $sports
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving sports', [
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
            // Check if user has admin permissions
            $userId = $request->user()?->id;
            if (!$userId || !$this->authClient->userHasPermission($userId, 'manage_sports')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.',
                    'error' => 'Insufficient permissions'
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
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sport created successfully',
                'data' => $sport
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating sport', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId ?? null
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
            $sport = Sport::withCount('tournaments')->find($id);

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
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving sport', [
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
            // Check if user has admin permissions
            $userId = $request->user()?->id;
            if (!$userId || !$this->authClient->userHasPermission($userId, 'manage_sports')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.',
                    'error' => 'Insufficient permissions'
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
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('sports')->ignore($sport->id)
                ],
                'team_based' => 'required|boolean',
                'rules' => 'nullable|string',
                'description' => 'nullable|string|max:1000'
            ]);

            $sport->update($validated);

            Log::info('Sport updated successfully', [
                'sport_id' => $sport->id,
                'name' => $sport->name,
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sport updated successfully',
                'data' => $sport
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating sport', [
                'sport_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId ?? null
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
    public function destroy(string $id): JsonResponse
    {
        try {
            // Check if user has admin permissions
            $userId = request()->user()?->id;
            if (!$userId || !$this->authClient->userHasPermission($userId, 'manage_sports')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.',
                    'error' => 'Insufficient permissions'
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

            // Check if sport has tournaments
            $tournamentCount = $sport->tournaments()->count();
            if ($tournamentCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete sport with associated tournaments',
                    'error' => 'Foreign key constraint',
                    'data' => [
                        'tournament_count' => $tournamentCount
                    ]
                ], 409);
            }

            $sport->delete();

            Log::info('Sport deleted successfully', [
                'sport_id' => $sport->id,
                'name' => $sport->name,
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sport deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting sport', [
                'sport_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete sport',
                'error' => 'Internal server error'
            ], 500);
        }
    }
}
