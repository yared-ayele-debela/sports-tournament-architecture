<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Services\Events\EventPublisher;
use App\Services\Events\EventPayloadBuilder;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class UserServiceController extends Controller
{
    protected EventPublisher $eventPublisher;

    public function __construct(EventPublisher $eventPublisher)
    {
        $this->eventPublisher = $eventPublisher;
    }
    /**
     * Get user details by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function getUserById($id): JsonResponse
    {
        try {
            $user = User::with('roles', 'roles.permissions')->find($id);
            
            if (!$user) {
                return ApiResponse::notFound('User not found');
            }

            return ApiResponse::success([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'roles' => $user->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description,
                        'permissions' => $role->permissions->map(function ($permission) {
                            return [
                                'id' => $permission->id,
                                'name' => $permission->name,
                                'description' => $permission->description,
                            ];
                        })
                    ];
                })
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting user by ID: ' . $e->getMessage());
            
            return ApiResponse::serverError('Internal server error', $e);
        }
    }

    /**
     * Assign role to user.
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function assignRole(Request $request, $userId): JsonResponse
    {
        try {
            $request->validate([
                'role_id' => 'required|exists:roles,id'
            ]);

            $user = User::find($userId);
            if (!$user) {
                return ApiResponse::notFound('User not found');
            }

            $role = Role::find($request->role_id);
            if (!$role) {
                return ApiResponse::notFound('Role not found');
            }

            // Check if user already has this role
            if ($user->roles()->where('role_id', $role->id)->exists()) {
                return ApiResponse::error('User already has this role', 409);
            }

            $user->roles()->attach($role->id);

            // Publish role assigned event
            $this->publishRoleAssignedEvent($user, $role, $request);

            return ApiResponse::success([
                'user_id' => $user->id,
                'role_id' => $role->id,
                'role_name' => $role->name
            ], 'Role assigned successfully');
        } catch (\Exception $e) {
            Log::error('Error assigning role to user: ' . $e->getMessage());
            
            return ApiResponse::serverError('Internal server error', $e);
        }
    }

    /**
     * Get all user permissions.
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function getUserPermissions($userId): JsonResponse
    {
        try {
            $user = User::find($userId);
            
            if (!$user) {
                return ApiResponse::notFound('User not found');
            }

            $permissions = $user->roles()
                ->with('permissions')
                ->get()
                ->pluck('permissions')
                ->flatten()
                ->unique('id')
                ->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'description' => $permission->description
                    ];
                });

            return ApiResponse::success([
                'user_id' => $user->id,
                'permissions' => $permissions
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting user permissions: ' . $e->getMessage());
            
            return ApiResponse::serverError('Internal server error', $e);
        }
    }

    /**
     * Validate if user exists by ID.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function validateUserById($id): JsonResponse
    {
        try {
            $user = User::find($id);
            
            if (!$user) {
                return ApiResponse::error('User not found', 404, ['exists' => false]);
            }

            return ApiResponse::success([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_active' => !is_null($user->email_verified_at),
                'exists' => true
            ], 'User exists');
        } catch (\Exception $e) {
            Log::error('Error validating user by ID: ' . $e->getMessage());
            
            return ApiResponse::serverError('Internal server error', $e, ['exists' => false]);
        }
    }

    /**
     * Publish role assigned event
     *
     * @param User $user
     * @param Role $role
     * @param Request $request
     * @return void
     */
    protected function publishRoleAssignedEvent(User $user, Role $role, Request $request): void
    {
        try {
            $payload = EventPayloadBuilder::userRoleAssigned(
                $user, 
                $role->name, 
                auth()->id() ?? 'system'
            );

            $this->eventPublisher->publish('sports.auth.user.role.assigned', $payload);
        } catch (\Exception $e) {
            Log::warning('Failed to publish role assigned event', [
                'user_id' => $user->id,
                'role_id' => $role->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}