<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!Auth::check()) {
            abort(401, 'Unauthorized');
        }

        if (!Auth::user()->hasRole($role)) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}
