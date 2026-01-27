<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use App\Services\AuthService;
use App\Services\Events\EventPublisher;
use App\Services\Events\EventPayloadBuilder;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class VenueController extends Controller
{
    protected AuthService $authService;
    protected EventPublisher $eventPublisher;

    public function __construct(AuthService $authService, EventPublisher $eventPublisher)
    {
        $this->authService = $authService;
        $this->eventPublisher = $eventPublisher;
    }
    /**
     * Display a listing of venues.
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->query('per_page', 20);
            $perPage = max(1, min(100, $perPage));

            $venues = Venue::orderByDesc('id')->paginate($perPage);

            return ApiResponse::paginated($venues, 'Venues retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve venues', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::serverError('Failed to retrieve venues', $e);
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

            // Publish venue created event
            $this->publishVenueCreatedEvent($venue, $user);

            return ApiResponse::created($venue, 'Venue created successfully');
        } catch (\Exception $e) {
            Log::error('Failed to create venue', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::serverError('Failed to create venue', $e);
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
                return ApiResponse::notFound('Venue not found');
            }

            return ApiResponse::success($venue, 'Venue retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve venue', [
                'venue_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::serverError('Failed to retrieve venue', $e);
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

            $oldData = $venue->toArray();
            $venue->update($validated);

            Log::info('Venue updated successfully', [
                'venue_id' => $venue->id,
                'name' => $venue->name,
                'user_id' => $user['id']
            ]);

            // Publish venue updated event
            $this->publishVenueUpdatedEvent($venue, $oldData);

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

            // Publish venue deleted event
            $this->publishVenueDeletedEvent($venue, $user);

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

    /**
     * Publish venue created event
     *
     * @param Venue $venue
     * @param array $user
     * @return void
     */
    protected function publishVenueCreatedEvent(Venue $venue, array $user): void
    {
        try {
            $payload = EventPayloadBuilder::venueCreated($venue, $user);
            $this->eventPublisher->publish('sports.venue.created', $payload);
        } catch (\Exception $e) {
            Log::warning('Failed to publish venue created event', [
                'venue_id' => $venue->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Publish venue updated event
     *
     * @param Venue $venue
     * @param array $oldData
     * @return void
     */
    protected function publishVenueUpdatedEvent(Venue $venue, array $oldData): void
    {
        try {
            $payload = EventPayloadBuilder::venueUpdated($venue, $oldData);
            $this->eventPublisher->publish('sports.venue.updated', $payload);
        } catch (\Exception $e) {
            Log::warning('Failed to publish venue updated event', [
                'venue_id' => $venue->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Publish venue deleted event
     *
     * @param Venue $venue
     * @param array $user
     * @return void
     */
    protected function publishVenueDeletedEvent(Venue $venue, array $user): void
    {
        try {
            $payload = EventPayloadBuilder::venueDeleted($venue, $user);
            $this->eventPublisher->publish('sports.venue.deleted', $payload);
        } catch (\Exception $e) {
            Log::warning('Failed to publish venue deleted event', [
                'venue_id' => $venue->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
