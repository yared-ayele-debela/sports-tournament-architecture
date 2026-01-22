<?php

namespace App\Services\Clients;

class AuthServiceClient extends ServiceClient
{
    public function __construct()
    {
        parent::__construct(config('services.auth.url', env('AUTH_SERVICE_URL', 'http://localhost:8001')));
    }

    /**
     * Validate user token
     */
    public function validateToken(string $token): array
    {
        return $this->get('/api/auth/me', [], ['auth'], 60);
    }

    /**
     * Get user by ID
     */
    public function getUser(int $userId): array
    {
        return $this->get("/api/users/{$userId}", [], ['auth'], 300);
    }

    /**
     * Get user permissions
     */
    public function getUserPermissions(int $userId): array
    {
        return $this->get("/api/users/{$userId}/permissions", [], ['auth'], 300);
    }

    /**
     * Get user roles
     */
    public function getUserRoles(int $userId): array
    {
        return $this->get("/api/users/{$userId}/roles", [], ['auth'], 300);
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(int $userId, string $permission): array
    {
        return $this->get("/api/users/{$userId}/permissions/check", [
            'permission' => $permission
        ], ['auth'], 60);
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(int $userId, string $role): array
    {
        return $this->get("/api/users/{$userId}/roles/check", [
            'role' => $role
        ], ['auth'], 60);
    }
}
