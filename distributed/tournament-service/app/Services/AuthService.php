<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthService
{
    /**
     * Validate user token with auth service
     *
     * @param string $token
     * @return array|null
     */
    public function validateToken(string $token): ?array
    {
        try {
            $response = Http::withToken($token)
                ->get(config('services.auth.url') . '/api/auth/me');

            if ($response->getStatusCode() === 200) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Auth service error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user by ID from auth service
     *
     * @param int $userId
     * @param string $token
     * @return array|null
     */
    public function getUserById(int $userId, string $token): ?array
    {
        try {
            $response = Http::withToken($token)
                ->get(config('services.auth.url') . "/api/users/{$userId}");

            if ($response->getStatusCode() === 200) {
                return $response->json();
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Auth service error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if user has specific permission
     *
     * @param array $user
     * @param string $permission
     * @return bool
     */
    public function userHasPermission(array $user, string $permission): bool
    {
        $permissions = collect($user['data']['permissions'] ?? []);
        return $permissions->contains('name', $permission);
    }

    /**
     * Check if user has specific role
     *
     * @param array $user
     * @param string $role
     * @return bool
     */
    public function userHasRole(array $user, string $role): bool
    {
        $roles = collect($user['data']['roles'] ?? []);
        return $roles->contains('name', $role);
    }
}
