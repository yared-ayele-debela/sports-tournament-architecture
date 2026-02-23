<?php

namespace App\Services\Clients;

class AuthServiceClient extends ServiceClient
{
    public function __construct()
    {
        parent::__construct(config('services.auth.url', env('AUTH_SERVICE_URL', 'http://auth-service:8001')));
    }

    public function validateUser($userId)
    {
        return $this->get("/api/users/{$userId}/validate");
    }
}
