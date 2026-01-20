<?php

namespace App\Services\Clients;

class AuthServiceClient extends ServiceClient
{
    public function __construct()
    {
        parent::__construct('http://localhost:8001');
    }

    public function validateUser($userId)
    {
        return $this->get("/api/users/{$userId}/validate");
    }
}
