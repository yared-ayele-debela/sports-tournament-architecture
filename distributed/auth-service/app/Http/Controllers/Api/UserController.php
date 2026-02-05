<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = $request->get('per_page', 15);
            $page = $request->get('page', 1);
            $search = $request->get('search');

            // Ensure per_page is a valid integer
            $perPage = (int) $perPage;
            if ($perPage < 1) {
                $perPage = 15;
            }
            if ($perPage > 100) {
                $perPage = 100; // Limit max per page
            }

            $query = User::with('roles', 'roles.permissions');

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            }

            $users = $query->paginate($perPage, ['*'], 'page', $page);

            $users->getCollection()->transform(function ($user) {
                return [
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
                        ];
                    }),
                ];
            });

            return ApiResponse::paginated($users, 'Users retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error listing users: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to retrieve users', $e);
        }
    }

    /**
     * Display the specified user.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
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
                        }),
                    ];
                }),
            ], 'User retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving user: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to retrieve user', $e);
        }
    }

    /**
     * Store a newly created user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'role_ids' => 'sometimes|array',
                'role_ids.*' => 'exists:roles,id',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Assign roles if provided
            if ($request->has('role_ids')) {
                $user->roles()->attach($request->role_ids);
            }

            $user->load('roles', 'roles.permissions');

            return ApiResponse::created([
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
                    ];
                }),
            ], 'User created successfully');
        } catch (\Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to create user', $e);
        }
    }

    /**
     * Update the specified user.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return ApiResponse::notFound('User not found');
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
                'password' => 'sometimes|string|min:8',
                'role_ids' => 'sometimes|array',
                'role_ids.*' => 'exists:roles,id',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $updateData = [];
            if ($request->has('name')) {
                $updateData['name'] = $request->name;
            }
            if ($request->has('email')) {
                $updateData['email'] = $request->email;
            }
            if ($request->has('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            if (!empty($updateData)) {
                $user->update($updateData);
            }

            // Sync roles if provided
            if ($request->has('role_ids')) {
                $user->roles()->sync($request->role_ids);
            }

            $user->load('roles', 'roles.permissions');

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
                    ];
                }),
            ], 'User updated successfully');
        } catch (\Exception $e) {
            Log::error('Error updating user: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to update user', $e);
        }
    }

    /**
     * Remove the specified user.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return ApiResponse::notFound('User not found');
            }

            // Prevent deleting yourself
            $authenticatedUser = auth('api')->user();
            if ($authenticatedUser && $user->id === $authenticatedUser->id) {
                return ApiResponse::error('You cannot delete your own account', 403);
            }

            $user->delete();

            return ApiResponse::success(null, 'User deleted successfully');
        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to delete user', $e);
        }
    }
}
