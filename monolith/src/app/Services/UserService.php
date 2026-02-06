<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Exceptions\BusinessLogicException;
use App\Exceptions\ResourceNotFoundException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserService
{
    /**
     * Create a new user with role assignment
     */
    public function createUser(array $userData, string $roleName): User
    {
        try {
            return DB::transaction(function () use ($userData, $roleName) {
                // Hash password
                $userData['password'] = Hash::make($userData['password']);

                // Create user
                $user = User::create($userData);

                // Assign role
                $role = Role::where('name', $roleName)->first();
                if (!$role) {
                    throw new ResourceNotFoundException('Role', null, "Role '{$roleName}' not found");
                }
                $user->roles()->attach($role->id);

                Log::info('User created', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'role' => $roleName,
                    'created_by' => auth()->id(),
                ]);

                return $user->fresh(['roles']);
            });
        } catch (ResourceNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to create user', [
                'user_data' => array_merge($userData, ['password' => '***']),
                'role' => $roleName,
                'error' => $e->getMessage(),
                'created_by' => auth()->id(),
            ]);

            throw new BusinessLogicException(
                $e->getMessage(),
                'Failed to create user. Please try again.',
                ['user_data' => $userData, 'role' => $roleName]
            );
        }
    }

    /**
     * Update user information and role
     */
    public function updateUser(User $user, array $userData, ?string $roleName = null, ?string $password = null): User
    {
        try {
            return DB::transaction(function () use ($user, $userData, $roleName, $password) {
                // Update user data
                $user->update($userData);

                // Update role if provided
                if ($roleName !== null) {
                    $newRole = Role::where('name', $roleName)->first();
                    if (!$newRole) {
                        throw new ResourceNotFoundException('Role', null, "Role '{$roleName}' not found");
                    }
                    $user->roles()->sync([$newRole->id]);
                }

                // Update password if provided
                if ($password !== null) {
                    $user->update([
                        'password' => Hash::make($password)
                    ]);
                }

                Log::info('User updated', [
                    'user_id' => $user->id,
                    'role' => $roleName,
                    'password_changed' => $password !== null,
                    'updated_by' => auth()->id(),
                ]);

                return $user->fresh(['roles']);
            });
        } catch (ResourceNotFoundException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to update user', [
                'user_id' => $user->id,
                'user_data' => $userData,
                'role' => $roleName,
                'error' => $e->getMessage(),
                'updated_by' => auth()->id(),
            ]);

            throw new BusinessLogicException(
                $e->getMessage(),
                'Failed to update user. Please try again.',
                ['user_id' => $user->id, 'user_data' => $userData]
            );
        }
    }

    /**
     * Delete user and detach all roles
     */
    public function deleteUser(User $user): bool
    {
        // Prevent deletion of self
        if ($user->id === Auth::id()) {
            throw new BusinessLogicException(
                'Cannot delete own account',
                'You cannot delete your own account.',
                ['user_id' => $user->id]
            );
        }

        try {
            return DB::transaction(function () use ($user) {
                $userId = $user->id;
                $userEmail = $user->email;

                $user->roles()->detach();
                $deleted = $user->delete();

                Log::info('User deleted', [
                    'deleted_user_id' => $userId,
                    'deleted_user_email' => $userEmail,
                    'deleted_by' => auth()->id(),
                ]);

                return $deleted;
            });
        } catch (BusinessLogicException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to delete user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'deleted_by' => auth()->id(),
            ]);

            throw new BusinessLogicException(
                $e->getMessage(),
                'Failed to delete user. Please try again.',
                ['user_id' => $user->id]
            );
        }
    }

    /**
     * Check if user can be deleted
     */
    public function canDeleteUser(User $user): bool
    {
        // Cannot delete yourself
        if ($user->id === Auth::id()) {
            return false;
        }

        // Add other business rules here (e.g., cannot delete last admin)
        return true;
    }

    /**
     * Get users with search and filter
     */
    public function searchUsers(?string $search = null, ?string $role = null)
    {
        $query = User::with('roles')->orderBy('created_at', 'desc');

        // Search by name or email
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($role) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('name', $role);
            });
        }

        return $query;
    }
}
