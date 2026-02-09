<?php

namespace App\Services;

use App\Exceptions\AuthenticationException;
use App\Exceptions\ServiceRequestException;
use App\Exceptions\ServiceUnavailableException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthService
{
    /**
     * Validate user token with auth service
     *
     * @param string $token
     * @return array
     * @throws AuthenticationException When token is invalid
     * @throws ServiceUnavailableException When auth service is unavailable
     */
    public function validateToken(string $token): array
    {
        try {
            $authServiceUrl = config('services.auth.url', env('AUTH_SERVICE_URL', 'http://auth-service:8001'));
            $response = Http::timeout(5)
                ->withToken($token)
                ->get($authServiceUrl . '/api/auth/me');

            $statusCode = $response->status();

            if ($statusCode === 200) {
                $data = $response->json();
                if (!($data['success'] ?? false)) {
                    throw new AuthenticationException(
                        $data['message'] ?? 'Token validation failed',
                        ['response' => $data]
                    );
                }
                return $data;
            }

            if ($statusCode === 401 || $statusCode === 403) {
                throw new AuthenticationException(
                    'Invalid or expired token',
                    ['status_code' => $statusCode]
                );
            }

            throw new ServiceRequestException(
                "Auth service returned status {$statusCode}",
                'auth-service',
                $statusCode,
                ['status_code' => $statusCode]
            );
        } catch (AuthenticationException | ServiceRequestException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Auth service error: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            throw new ServiceUnavailableException(
                'Authentication service unavailable',
                'auth-service',
                ['error' => $e->getMessage()],
                $e
            );
        }
    }

    /**
     * Get user by ID from auth service
     *
     * @param int $userId
     * @param string $token
     * @return array
     * @throws AuthenticationException When token is invalid
     * @throws ServiceRequestException When request fails
     * @throws ServiceUnavailableException When auth service is unavailable
     */
    public function getUserById(int $userId, string $token): array
    {
        try {
            $authServiceUrl = config('services.auth.url', env('AUTH_SERVICE_URL', 'http://auth-service:8001'));
            $response = Http::timeout(5)
                ->withToken($token)
                ->get($authServiceUrl . "/api/users/{$userId}");

            $statusCode = $response->status();

            if ($statusCode === 200) {
                $data = $response->json();
                if (!($data['success'] ?? false)) {
                    throw new ServiceRequestException(
                        $data['message'] ?? 'Failed to retrieve user',
                        'auth-service',
                        $statusCode,
                        ['user_id' => $userId, 'response' => $data]
                    );
                }
                return $data;
            }

            if ($statusCode === 404) {
                throw new ServiceRequestException(
                    "User with ID {$userId} not found",
                    'auth-service',
                    404,
                    ['user_id' => $userId]
                );
            }

            if ($statusCode === 401 || $statusCode === 403) {
                throw new AuthenticationException(
                    'Invalid or expired token',
                    ['status_code' => $statusCode, 'user_id' => $userId]
                );
            }

            throw new ServiceRequestException(
                "Auth service returned status {$statusCode}",
                'auth-service',
                $statusCode,
                ['user_id' => $userId, 'status_code' => $statusCode]
            );
        } catch (AuthenticationException | ServiceRequestException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Auth service error: ' . $e->getMessage(), [
                'user_id' => $userId,
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            throw new ServiceUnavailableException(
                'Authentication service unavailable',
                'auth-service',
                ['user_id' => $userId, 'error' => $e->getMessage()],
                $e
            );
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
