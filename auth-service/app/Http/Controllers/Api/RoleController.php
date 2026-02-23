<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');

            $query = Role::with('permissions', 'users');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $roles = $query->paginate($perPage);

            $roles->getCollection()->transform(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'description' => $role->description,
                    'created_at' => $role->created_at,
                    'updated_at' => $role->updated_at,
                    'permissions_count' => $role->permissions->count(),
                    'users_count' => $role->users->count(),
                ];
            });

            return ApiResponse::paginated($roles, 'Roles retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error listing roles: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to retrieve roles', $e);
        }
    }

    /**
     * Display the specified role.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $role = Role::with('permissions', 'users')->find($id);

            if (!$role) {
                return ApiResponse::notFound('Role not found');
            }

            return ApiResponse::success([
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'created_at' => $role->created_at,
                'updated_at' => $role->updated_at,
                'permissions' => $role->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'description' => $permission->description,
                    ];
                }),
                'users' => $role->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ];
                }),
            ], 'Role retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving role: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to retrieve role', $e);
        }
    }

    /**
     * Store a newly created role.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:roles',
                'description' => 'nullable|string|max:500',
                'permission_ids' => 'sometimes|array',
                'permission_ids.*' => 'exists:permissions,id',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $role = Role::create([
                'name' => $request->name,
                'description' => $request->description ?? null,
            ]);

            // Assign permissions if provided
            if ($request->has('permission_ids')) {
                $role->permissions()->attach($request->permission_ids);
            }

            $role->load('permissions');

            return ApiResponse::created([
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'created_at' => $role->created_at,
                'updated_at' => $role->updated_at,
                'permissions' => $role->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'description' => $permission->description,
                    ];
                }),
            ], 'Role created successfully');
        } catch (\Exception $e) {
            Log::error('Error creating role: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to create role', $e);
        }
    }

    /**
     * Update the specified role.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $role = Role::find($id);

            if (!$role) {
                return ApiResponse::notFound('Role not found');
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255|unique:roles,name,' . $id,
                'description' => 'nullable|string|max:500',
                'permission_ids' => 'sometimes|array',
                'permission_ids.*' => 'exists:permissions,id',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $updateData = [];
            if ($request->has('name')) {
                $updateData['name'] = $request->name;
            }
            if ($request->has('description')) {
                $updateData['description'] = $request->description;
            }

            if (!empty($updateData)) {
                $role->update($updateData);
            }

            // Sync permissions if provided
            if ($request->has('permission_ids')) {
                $role->permissions()->sync($request->permission_ids);
            }

            $role->load('permissions', 'users');

            return ApiResponse::success([
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'created_at' => $role->created_at,
                'updated_at' => $role->updated_at,
                'permissions' => $role->permissions->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'description' => $permission->description,
                    ];
                }),
            ], 'Role updated successfully');
        } catch (\Exception $e) {
            Log::error('Error updating role: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to update role', $e);
        }
    }

    /**
     * Remove the specified role.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $role = Role::find($id);

            if (!$role) {
                return ApiResponse::notFound('Role not found');
            }

            // Check if role has users assigned
            if ($role->users()->count() > 0) {
                return ApiResponse::error(
                    'Cannot delete role. It is assigned to ' . $role->users()->count() . ' user(s). Please remove users from this role first.',
                    409
                );
            }

            $role->delete();

            return ApiResponse::success(null, 'Role deleted successfully');
        } catch (\Exception $e) {
            Log::error('Error deleting role: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to delete role', $e);
        }
    }
}
