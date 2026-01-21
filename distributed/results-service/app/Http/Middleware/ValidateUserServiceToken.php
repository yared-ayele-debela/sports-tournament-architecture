<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ValidateUserServiceToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token required',
                'error' => 'Unauthorized'
            ], 401);
        }

        // Validate token with auth service
        try {
            $response = Http::withToken($token)
                ->get(config('services.auth.url', env('AUTH_SERVICE_URL', 'http://localhost:8001')) . '/api/auth/me');

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token',
                    'error' => 'Unauthorized'
                ], 401);
            }

            $responseData = $response->json();

            if (!$responseData['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token validation failed',
                    'error' => 'Unauthorized'
                ], 401);
            }

            // Add user data to request for later use
            $request->merge([
                'user' => $responseData['data']['user'] ?? $responseData['data'],
                'user_id' => $responseData['data']['user']['id'] ?? $responseData['data']['id'],
                'user_permissions' => $responseData['data']['permissions'] ?? [],
                'user_roles' => $responseData['data']['roles'] ?? []
            ]);

            return $next($request);

        } catch (\Exception $e) {
            Log::error("Auth service error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Authentication service unavailable',
                'error' => 'Service Unavailable'
            ], 503);
        }
    }
}
