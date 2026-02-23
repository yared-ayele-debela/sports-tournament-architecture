<?php

namespace App\Http\Middleware;

use App\Services\TokenValidationCache;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateUserServiceToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token required',
                'error' => 'Unauthorized'
            ], 401);
        }

        // Validate token with caching
        try {
            $tokenCache = app(TokenValidationCache::class);
            $userData = $tokenCache->validate($token);

            if ($userData === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token',
                    'error' => 'Unauthorized'
                ], 401);
            }

            // Add user data to request for later use
            $request->merge([
                'authenticated_user' => $userData['user'] ?? null,
                'user_permissions' => $userData['permissions'] ?? [],
                'user_roles' => $userData['roles'] ?? []
            ]);

            return $next($request);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication service unavailable',
                'error' => 'Service Unavailable'
            ], 503);
        }
    }
}
