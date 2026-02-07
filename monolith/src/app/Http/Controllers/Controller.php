<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Check if the authenticated user has a specific permission.
     * Throws 403 if user doesn't have permission.
     *
     * @param string $permission
     * @return void
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    protected function checkPermission(string $permission): void
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'Unauthenticated');
        }

        // Check if user has admin role (bypass all checks)
        if ($user->hasRole('admin') || $user->hasRole('Administrator')) {
            return;
        }

        // Check if user has the permission
        if (!$user->hasPermission($permission)) {
            abort(403, 'Access Denied');
        }
    }
}
