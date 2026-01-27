<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sport;
use App\Services\AuthService;
use App\Services\Events\EventPublisher;
use App\Services\Events\EventPayloadBuilder;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SportController extends Controller
{
    protected AuthService $authService;
    protected EventPublisher $eventPublisher;

    public function __construct(AuthService $authService, EventPublisher $eventPublisher)
    {
        $this->authService = $authService;
        $this->eventPublisher = $eventPublisher;
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

            return ApiResponse::serverError('Failed to retrieve sports', $e);
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

            // Publish sport created event
            $this->publishSportCreatedEvent($sport, $user);

            return ApiResponse::created($sport, 'Sport created successfully');
        } catch (\Exception $e) {
            Log::error('Failed to create sport', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::serverError('Failed to create sport', $e);
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
                return ApiResponse::notFound('Sport not found');
            }

            return ApiResponse::success($sport, 'Sport retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve sport', [
                'sport_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::serverError('Failed to retrieve sport', $e);
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

            $oldData = $sport->toArray();
            $sport->update($validated);

            Log::info('Sport updated successfully', [
                'sport_id' => $sport->id,
                'name' => $sport->name,
                'user_id' => $user['id']
            ]);

            // Publish sport updated event
            $this->publishSportUpdatedEvent($sport, $oldData);

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

            // Publish sport deleted event
            $this->publishSportDeletedEvent($sport, $user);

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

    /**
     * Publish sport created event
     *
     * @param Sport $sport
     * @param array $user
     * @return void
     */
    protected function publishSportCreatedEvent(Sport $sport, array $user): void
    {
        try {
            $payload = EventPayloadBuilder::sportCreated($sport, $user);
            $this->eventPublisher->publish('sports.sport.created', $payload);
        } catch (\Exception $e) {
            Log::warning('Failed to publish sport created event', [
                'sport_id' => $sport->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Publish sport updated event
     *
     * @param Sport $sport
     * @param array $oldData
     * @return void
     */
    protected function publishSportUpdatedEvent(Sport $sport, array $oldData): void
    {
        try {
            $payload = EventPayloadBuilder::sportUpdated($sport, $oldData);
            $this->eventPublisher->publish('sports.sport.updated', $payload);
        } catch (\Exception $e) {
            Log::warning('Failed to publish sport updated event', [
                'sport_id' => $sport->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Publish sport deleted event
     *
     * @param Sport $sport
     * @param array $user
     * @return void
     */
    protected function publishSportDeletedEvent(Sport $sport, array $user): void
    {
        try {
            $payload = EventPayloadBuilder::sportDeleted($sport, $user);
            $this->eventPublisher->publish('sports.sport.deleted', $payload);
        } catch (\Exception $e) {
            Log::warning('Failed to publish sport deleted event', [
                'sport_id' => $sport->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
