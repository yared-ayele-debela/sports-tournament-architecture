<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');

            $query = Permission::with('roles');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            $permissions = $query->paginate($perPage);

            $permissions->getCollection()->transform(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'description' => $permission->description,
                    'created_at' => $permission->created_at,
                    'updated_at' => $permission->updated_at,
                    'roles_count' => $permission->roles->count(),
                ];
            });

            return ApiResponse::paginated($permissions, 'Permissions retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error listing permissions: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to retrieve permissions', $e);
        }
    }

    /**
     * Display the specified permission.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $permission = Permission::with('roles')->find($id);

            if (!$permission) {
                return ApiResponse::notFound('Permission not found');
            }

            return ApiResponse::success([
                'id' => $permission->id,
                'name' => $permission->name,
                'description' => $permission->description,
                'created_at' => $permission->created_at,
                'updated_at' => $permission->updated_at,
                'roles' => $permission->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description,
                    ];
                }),
            ], 'Permission retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving permission: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to retrieve permission', $e);
        }
    }

    /**
     * Store a newly created permission.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255|unique:permissions',
                'description' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $permission = Permission::create([
                'name' => $request->name,
                'description' => $request->description ?? null,
            ]);

            return ApiResponse::created([
                'id' => $permission->id,
                'name' => $permission->name,
                'description' => $permission->description,
                'created_at' => $permission->created_at,
                'updated_at' => $permission->updated_at,
            ], 'Permission created successfully');
        } catch (\Exception $e) {
            Log::error('Error creating permission: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to create permission', $e);
        }
    }

    /**
     * Update the specified permission.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $permission = Permission::find($id);

            if (!$permission) {
                return ApiResponse::notFound('Permission not found');
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255|unique:permissions,name,' . $id,
                'description' => 'nullable|string|max:500',
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
                $permission->update($updateData);
            }

            $permission->load('roles');

            return ApiResponse::success([
                'id' => $permission->id,
                'name' => $permission->name,
                'description' => $permission->description,
                'created_at' => $permission->created_at,
                'updated_at' => $permission->updated_at,
                'roles' => $permission->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description,
                    ];
                }),
            ], 'Permission updated successfully');
        } catch (\Exception $e) {
            Log::error('Error updating permission: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to update permission', $e);
        }
    }

    /**
     * Remove the specified permission.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $permission = Permission::find($id);

            if (!$permission) {
                return ApiResponse::notFound('Permission not found');
            }

            // Check if permission is assigned to any roles
            if ($permission->roles()->count() > 0) {
                return ApiResponse::error(
                    'Cannot delete permission. It is assigned to ' . $permission->roles()->count() . ' role(s). Please remove permission from roles first.',
                    409
                );
            }

            $permission->delete();

            return ApiResponse::success(null, 'Permission deleted successfully');
        } catch (\Exception $e) {
            Log::error('Error deleting permission: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to delete permission', $e);
        }
    }
}
