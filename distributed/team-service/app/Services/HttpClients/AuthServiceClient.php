<?php

namespace App\Services\HttpClients;

use Illuminate\Support\Facades\Log;

class AuthServiceClient extends ServiceClient
{
    /**
     * Create a new Auth Service client instance.
     */
    public function __construct(?string $jwtToken = null)
    {
        $authServiceUrl = config('services.auth.url', env('AUTH_SERVICE_URL', 'http://auth-service:8000'));
        parent::__construct($authServiceUrl, $jwtToken);
    }

    /**
     * Validate if a user exists and is active.
     *
     * @param int $userId
     * @return bool True if user exists and is active, false otherwise
     */
    public function validateUser(int $userId): bool
    {
        try {
            $response = $this->post('/api/users/validate', [
                'user_id' => $userId
            ]);

            if (!$response) {
                Log::error("Failed to validate user {$userId}: No response from auth service");
                return false;
            }

            $success = $response['success'] ?? false;
            $exists = $response['exists'] ?? false;

            if (!$success || !$exists) {
                Log::warning("User validation failed", [
                    'user_id' => $userId,
                    'response' => $response
                ]);
                return false;
            }

            Log::info("User validation successful", [
                'user_id' => $userId,
                'response' => $response
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Exception during user validation", [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get all permissions for a user.
     *
     * @param int $userId
     * @return array Array of permissions with id, name, and description
     */
    public function getUserPermissions(int $userId): array
    {
        try {
            $response = $this->get("/api/users/{$userId}/permissions");

            if (!$response) {
                Log::error("Failed to get permissions for user {$userId}: No response from auth service");
                return [];
            }

            $success = $response['success'] ?? false;
            $permissions = $response['data']['permissions'] ?? [];

            if (!$success) {
                Log::warning("Failed to get user permissions", [
                    'user_id' => $userId,
                    'response' => $response
                ]);
                return [];
            }

            Log::info("Successfully retrieved user permissions", [
                'user_id' => $userId,
                'permission_count' => count($permissions)
            ]);

            return $permissions;
        } catch (\Exception $e) {
            Log::error("Exception while getting user permissions", [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Get user details by ID.
     *
     * @param int $userId
     * @return array|null User data array or null if not found
     */
    public function getUser(int $userId): ?array
    {
        try {
            $response = $this->get("/api/users/{$userId}");

            if (!$response) {
                Log::error("Failed to get user {$userId}: No response from auth service");
                return null;
            }

            $success = $response['success'] ?? false;
            $userData = $response['data'] ?? null;

            if (!$success || !$userData) {
                Log::warning("User not found or access denied", [
                    'user_id' => $userId,
                    'response' => $response
                ]);
                return null;
            }

            Log::info("Successfully retrieved user data", [
                'user_id' => $userId,
                'user_name' => $userData['name'] ?? 'Unknown'
            ]);

            return $userData;
        } catch (\Exception $e) {
            Log::error("Exception while getting user data", [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Check if user has specific permission.
     *
     * @param int $userId
     * @param string $permissionName
     * @return bool True if user has the permission
     */
    public function userHasPermission(int $userId, string $permissionName): bool
    {
        $permissions = $this->getUserPermissions($userId);
        
        foreach ($permissions as $permission) {
            if ($permission['name'] === $permissionName) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has any of the specified permissions.
     *
     * @param int $userId
     * @param array $permissionNames
     * @return bool True if user has any of the permissions
     */
    public function userHasAnyPermission(int $userId, array $permissionNames): bool
    {
        $permissions = $this->getUserPermissions($userId);
        $userPermissionNames = array_column($permissions, 'name');
        
        return !empty(array_intersect($permissionNames, $userPermissionNames));
    }

    /**
     * Check if user has all specified permissions.
     *
     * @param int $userId
     * @param array $permissionNames
     * @return bool True if user has all permissions
     */
    public function userHasAllPermissions(int $userId, array $permissionNames): bool
    {
        $permissions = $this->getUserPermissions($userId);
        $userPermissionNames = array_column($permissions, 'name');
        
        return empty(array_diff($permissionNames, $userPermissionNames));
    }

    /**
     * Get user roles.
     *
     * @param int $userId
     * @return array Array of user roles
     */
    public function getUserRoles(int $userId): array
    {
        try {
            $response = $this->get("/api/users/{$userId}");

            if (!$response || !($response['success'] ?? false)) {
                Log::warning("Failed to get user roles", [
                    'user_id' => $userId,
                    'response' => $response ?? 'No response'
                ]);
                return [];
            }

            $roles = $response['data']['roles'] ?? [];
            
            Log::info("Successfully retrieved user roles", [
                'user_id' => $userId,
                'role_count' => count($roles)
            ]);

            return $roles;
        } catch (\Exception $e) {
            Log::error("Exception while getting user roles", [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Assign role to user (admin only).
     *
     * @param int $userId
     * @param int $roleId
     * @return bool True if assignment was successful
     */
    public function assignRoleToUser(int $userId, int $roleId): bool
    {
        try {
            $response = $this->post("/api/users/{$userId}/roles", [
                'role_id' => $roleId
            ]);

            if (!$response) {
                Log::error("Failed to assign role to user {$userId}: No response from auth service");
                return false;
            }

            $success = $response['success'] ?? false;

            if (!$success) {
                Log::warning("Role assignment failed", [
                    'user_id' => $userId,
                    'role_id' => $roleId,
                    'response' => $response
                ]);
                return false;
            }

            Log::info("Successfully assigned role to user", [
                'user_id' => $userId,
                'role_id' => $roleId,
                'response' => $response
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Exception while assigning role to user", [
                'user_id' => $userId,
                'role_id' => $roleId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Test connection to auth service.
     *
     * @return bool True if connection is successful
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->get('/api/health');
            
            if (!$response) {
                return false;
            }

            return $response['success'] ?? false;
        } catch (\Exception $e) {
            Log::error("Auth service connection test failed", [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
