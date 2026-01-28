<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class RefereeAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            abort(401, 'Unauthorized');
        }

        if (!Auth::user()->hasRole('referee')) {
            abort(403, 'Forbidden - Referee access required');
        }

        return $next($request);
    }
}
