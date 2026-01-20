<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class ValidatePassportToken
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

        // Validate token with auth service
        try {
            $response = Http::withToken($token)
                ->get(config('services.auth.url') . '/api/auth/me');

            if ($response->status() !== 200) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid token',
                    'error' => 'Unauthorized'
                ], 401);
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
            return response()->json([
                'success' => false,
                'message' => 'Authentication service unavailable',
                'error' => 'Service Unavailable'
            ], 503);
        }
    }
}
