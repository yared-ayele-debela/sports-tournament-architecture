<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!Auth::check()) {
            abort(401, 'Unauthorized');
        }

        if (!Auth::user()->hasPermission($permission)) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}
