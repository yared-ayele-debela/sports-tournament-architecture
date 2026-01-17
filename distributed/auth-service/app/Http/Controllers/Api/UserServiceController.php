<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class UserServiceController extends Controller
{
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
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
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
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting user by ID: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $role = Role::find($request->role_id);
            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found'
                ], 404);
            }

            // Check if user already has this role
            if ($user->roles()->where('role_id', $role->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'User already has this role'
                ], 409);
            }

            $user->roles()->attach($role->id);

            return response()->json([
                'success' => true,
                'message' => 'Role assigned successfully',
                'data' => [
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                    'role_name' => $role->name
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error assigning role to user: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
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
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
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

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'permissions' => $permissions
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting user permissions: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Validate if user exists.
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function validateUser($userId): JsonResponse
    {
        try {
            $user = User::find($userId);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'exists' => false
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'User exists',
                'exists' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => !is_null($user->email_verified_at)
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error validating user: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'exists' => false
            ], 500);
        }
    }
}