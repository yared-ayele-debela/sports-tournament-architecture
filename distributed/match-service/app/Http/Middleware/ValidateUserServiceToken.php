<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
            return \App\Support\ApiResponse::unauthorized('Token required');
        }

        // Validate token with auth service
        try {
            $response = Http::withToken($token)
                ->get(config('services.auth.url') . '/api/auth/me');

            if ($response->status() !== 200) {
                return \App\Support\ApiResponse::unauthorized('Invalid token');
            }

            $responseData = $response->json();

            // Add user data to request for later use
            $request->merge([
                'authenticated_user' => $responseData['data']['user'] ?? null,
                'user_permissions' => $responseData['data']['permissions'] ?? [],
                'user_roles' => $responseData['data']['roles'] ?? []
            ]);

            return $next($request);
        } catch (\Exception $e) {
            return \App\Support\ApiResponse::serviceUnavailable('Authentication service unavailable', 'AUTH');
        }
    }
}
