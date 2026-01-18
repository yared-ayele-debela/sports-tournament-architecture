<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\Sport;
use App\Services\HttpClients\AuthServiceClient;
use App\Services\EventPublisher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TournamentController extends Controller
{
    protected AuthServiceClient $authClient;
    protected EventPublisher $eventPublisher;

    /**
     * Create a new TournamentController instance.
     */
    public function __construct(AuthServiceClient $authClient, EventPublisher $eventPublisher)
    {
        $this->authClient = $authClient;
        $this->eventPublisher = $eventPublisher;
    }

    /**
     * Display a listing of tournaments with filters.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Tournament::with(['sport', 'settings']);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->get('status'));
            }

            if ($request->has('sport_id')) {
                $query->where('sport_id', $request->get('sport_id'));
            }

            if ($request->has('start_date_from')) {
                $query->where('start_date', '>=', $request->get('start_date_from'));
            }

            if ($request->has('start_date_to')) {
                $query->where('start_date', '<=', $request->get('start_date_to'));
            }

            $tournaments = $query->orderBy('created_at', 'desc')->paginate(15);

            return response()->json([
                'success' => true,
                'message' => 'Tournaments retrieved successfully',
                'data' => $tournaments
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving tournaments', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tournaments',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created tournament (Admin only).
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Check if user has admin permissions
            $userId = $request->user()?->id;
            if (!$userId || !$this->authClient->userHasPermission($userId, 'manage_tournaments')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.',
                    'error' => 'Insufficient permissions'
                ], 403);
            }

            // Validate user exists via Auth service
            if (!$this->authClient->validateUser($userId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User validation failed',
                    'error' => 'Invalid user'
                ], 401);
            }

            $validated = $request->validate([
                'sport_id' => 'required|exists:sports,id',
                'name' => 'required|string|max:255',
                'location' => 'required|string|max:500',
                'start_date' => 'required|date|after:today',
                'end_date' => 'required|date|after:start_date',
                'status' => 'nullable|in:planning,active,completed,cancelled'
            ]);

            $tournament = Tournament::create($validated);

            // Publish tournament created event
            $this->eventPublisher->publishTournamentCreated(
                $tournament->load(['sport', 'settings'])->toArray(),
                $userId
            );

            Log::info('Tournament created successfully', [
                'tournament_id' => $tournament->id,
                'name' => $tournament->name,
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tournament created successfully',
                'data' => $tournament->load(['sport', 'settings'])
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating tournament', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create tournament',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified tournament with settings.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $tournament = Tournament::with(['sport', 'settings'])->find($id);

            if (!$tournament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Tournament retrieved successfully',
                'data' => $tournament
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error retrieving tournament', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tournament',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update the specified tournament.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            // Check if user has admin permissions
            $userId = $request->user()?->id;
            if (!$userId || !$this->authClient->userHasPermission($userId, 'manage_tournaments')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.',
                    'error' => 'Insufficient permissions'
                ], 403);
            }

            $tournament = Tournament::find($id);

            if (!$tournament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            $validated = $request->validate([
                'sport_id' => 'required|exists:sports,id',
                'name' => 'required|string|max:255',
                'location' => 'required|string|max:500',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'status' => 'nullable|in:planning,active,completed,cancelled'
            ]);

            $oldData = $tournament->toArray();
            $tournament->update($validated);
            $newData = $tournament->fresh()->toArray();

            // Calculate changes for event payload
            $changes = $this->calculateChanges($oldData, $newData);

            // Publish tournament updated event
            $this->eventPublisher->publishTournamentUpdated(
                $tournament->load(['sport', 'settings'])->toArray(),
                $userId,
                $changes
            );

            Log::info('Tournament updated successfully', [
                'tournament_id' => $tournament->id,
                'name' => $tournament->name,
                'user_id' => $userId,
                'changes_count' => count($changes)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tournament updated successfully',
                'data' => $tournament->load(['sport', 'settings'])
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating tournament', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update tournament',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified tournament.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            // Check if user has admin permissions
            $userId = request()->user()?->id;
            if (!$userId || !$this->authClient->userHasPermission($userId, 'manage_tournaments')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.',
                    'error' => 'Insufficient permissions'
                ], 403);
            }

            $tournament = Tournament::find($id);

            if (!$tournament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            $tournament->delete();

            Log::info('Tournament deleted successfully', [
                'tournament_id' => $tournament->id,
                'name' => $tournament->name,
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tournament deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting tournament', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tournament',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Change tournament status (planning → active → completed).
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        try {
            // Check if user has admin permissions
            $userId = $request->user()?->id;
            if (!$userId || !$this->authClient->userHasPermission($userId, 'manage_tournaments')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Admin access required.',
                    'error' => 'Insufficient permissions'
                ], 403);
            }

            $tournament = Tournament::find($id);

            if (!$tournament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            $validated = $request->validate([
                'status' => 'required|in:planning,active,completed,cancelled'
            ]);

            $oldStatus = $tournament->status;
            $tournament->update(['status' => $validated['status']]);

            // Publish tournament status changed event
            $this->eventPublisher->publishTournamentStatusChanged(
                $tournament->load(['sport', 'settings'])->toArray(),
                $oldStatus,
                $validated['status'],
                $userId
            );

            Log::info('Tournament status updated', [
                'tournament_id' => $tournament->id,
                'old_status' => $oldStatus,
                'new_status' => $validated['status'],
                'user_id' => $userId
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tournament status updated successfully',
                'data' => [
                    'old_status' => $oldStatus,
                    'new_status' => $validated['status']
                ]
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating tournament status', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update tournament status',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Calculate changes between old and new data.
     *
     * @param array $oldData
     * @param array $newData
     * @return array
     */
    protected function calculateChanges(array $oldData, array $newData): array
    {
        $changes = [];
        
        foreach ($newData as $key => $newValue) {
            $oldValue = $oldData[$key] ?? null;
            
            if ($oldValue !== $newValue) {
                $changes[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }
        
        return $changes;
    }
}
