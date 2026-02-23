<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AuthServiceClient extends ServiceClient
{
    protected function getBaseUrl()
    {
        return env('AUTH_SERVICE_URL', 'http://auth-service:8001');
    }

    public function validateUser($userId)
    {
        return $this->request('GET', "/api/users/{$userId}/validate");
    }

    /**
     * Get user details by ID
     *
     * @param int $userId
     * @return array|null
     */
    public function getUser($userId): ?array
    {
        try {
            $response = $this->request('GET', "/api/users/{$userId}");
            if (isset($response['success']) && $response['success'] && isset($response['data'])) {
                return $response['data'];
            }
            Log::warning('Auth Service returned unsuccessful response for user', [
                'user_id' => $userId,
                'response' => $response
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Failed to fetch user from Auth Service', [
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}
