<?php

namespace App\Services;

class AuthServiceClient extends ServiceClient
{
    protected function getBaseUrl()
    {
        return env('AUTH_SERVICE_URL', 'http://localhost:8001');
    }

    public function validateUser($userId)
    {
        return $this->request('GET', "/api/users/{$userId}/validate");
    }
}
