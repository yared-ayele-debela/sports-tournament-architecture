<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\TournamentSettings;
use App\Services\AuthService;
use App\Services\EventPublisher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TournamentController extends Controller
{
    protected AuthService $authService;
    protected EventPublisher $eventPublisher;

    public function __construct(AuthService $authService, EventPublisher $eventPublisher)
    {
        $this->authService = $authService;
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
                $query->where('status', $request->status);
            }

            if ($request->has('sport_id')) {
                $query->where('sport_id', $request->sport_id);
            }

            $tournaments = $query->get();

            return response()->json([
                'success' => true,
                'message' => 'Tournaments retrieved successfully',
                'data' => $tournaments
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve tournaments', [
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
            
            // Check if user has admin role OR manage_tournaments permission
            $isAdmin = collect($userRoles)->contains('name', 'Administrator');
            $canManageTournaments = $this->authService->userHasPermission(['data' => ['permissions' => $userPermissions]], 'manage_tournaments');
            
            if (!$isAdmin && !$canManageTournaments) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Tournament management access required.',
                    'error' => 'Insufficient permissions',
                    'user_roles' => $userRoles,
                    'user_permissions' => $userPermissions
                ], 403);
            }

            $validated = $request->validate([
                'sport_id' => 'required|exists:sports,id',
                'name' => 'required|string|max:255',
                'location' => 'nullable|string|max:500',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after:start_date',
                'status' => 'sometimes|in:planned,ongoing,completed,cancelled'
            ]);

            $validated['created_by'] = $user['id'];

            $tournament = Tournament::create($validated);

            Log::info('Tournament created successfully', [
                'tournament_id' => $tournament->id,
                'name' => $tournament->name,
                'user_id' => $user['id']
            ]);

            // Publish tournament created event
            $this->eventPublisher->publishTournamentCreated($tournament->load(['sport', 'settings'])->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Tournament created successfully',
                'data' => $tournament->load(['sport', 'settings'])
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create tournament', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve tournament', [
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
            $tournament = Tournament::find($id);

            if (!$tournament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            $validated = $request->validate([
                'sport_id' => 'sometimes|exists:sports,id',
                'name' => 'sometimes|string|max:255',
                'location' => 'nullable|string|max:500',
                'start_date' => 'sometimes|date|after_or_equal:today',
                'end_date' => 'sometimes|date|after:start_date',
                'status' => 'sometimes|in:planned,ongoing,completed,cancelled'
            ]);

            $oldData = $tournament->toArray();
            $tournament->update($validated);

            Log::info('Tournament updated successfully', [
                'tournament_id' => $tournament->id,
                'name' => $tournament->name
            ]);

            // Publish tournament updated event
            $this->eventPublisher->publishTournamentUpdated(
                $tournament->load(['sport', 'settings'])->toArray(),
                $oldData
            );

            return response()->json([
                'success' => true,
                'message' => 'Tournament updated successfully',
                'data' => $tournament->load(['sport', 'settings'])
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update tournament', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
                'tournament_id' => $id,
                'tournament_name' => $tournament->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tournament deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete tournament', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tournament',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update tournament status.
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        try {
            $tournament = Tournament::find($id);

            if (!$tournament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            $validated = $request->validate([
                'status' => 'required|in:planned,ongoing,completed,cancelled'
            ]);

            // Validate status transition
            $currentStatus = $tournament->status;
            $newStatus = $validated['status'];

            if (!$this->isValidStatusTransition($currentStatus, $newStatus)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status transition',
                    'error' => "Cannot transition from {$currentStatus} to {$newStatus}",
                    'current_status' => $currentStatus,
                    'requested_status' => $newStatus
                ], 400);
            }

            $tournament->update(['status' => $newStatus]);

            Log::info('Tournament status updated successfully', [
                'tournament_id' => $tournament->id,
                'old_status' => $currentStatus,
                'new_status' => $newStatus
            ]);

            // Publish tournament status changed event
            $this->eventPublisher->publishTournamentStatusChanged(
                $tournament->load(['sport', 'settings'])->toArray(),
                $currentStatus
            );

            return response()->json([
                'success' => true,
                'message' => 'Tournament status updated successfully',
                'data' => $tournament
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update tournament status', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update tournament status',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Validate if a tournament exists and is accessible.
     */
    public function validate(string $id): JsonResponse
    {
        try {
            $tournament = Tournament::find($id);

            if (!$tournament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Tournament is valid',
                'data' => [
                    'id' => $tournament->id,
                    'name' => $tournament->name,
                    'status' => $tournament->status,
                    'sport_id' => $tournament->sport_id
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to validate tournament', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to validate tournament',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Validate if status transition is allowed.
     */
    private function isValidStatusTransition(string $from, string $to): bool
    {
        $validTransitions = [
            'planned' => ['ongoing', 'cancelled'],
            'ongoing' => ['completed', 'cancelled'],
            'completed' => [], // No transitions from completed
            'cancelled' => ['planned'] // Can restart cancelled tournaments
        ];

        return in_array($to, $validTransitions[$from] ?? []);
    }
}
