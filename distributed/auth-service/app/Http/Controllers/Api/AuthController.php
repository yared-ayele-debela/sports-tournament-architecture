<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\Token;

class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return \App\Support\ApiResponse::validationError($validator->errors());
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('Personal Access Token')->accessToken;

            return \App\Support\ApiResponse::created([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ], 'User registered successfully');
        } catch (\Exception $e) {
            return \App\Support\ApiResponse::serverError('Registration failed', $e);
        }
    }

    /**
     * Authenticate user and return JWT token.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6',
            ]);

            if ($validator->fails()) {
                return \App\Support\ApiResponse::validationError($validator->errors());
            }

            $credentials = $request->only('email', 'password');

            if (!auth()->attempt($credentials)) {
                return \App\Support\ApiResponse::unauthorized('Invalid credentials');
            }

            $user = auth()->user();
            $token = $user->createToken('Personal Access Token')->accessToken;

            return \App\Support\ApiResponse::success([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ], 'Login successful');
        } catch (\Exception $e) {
            return \App\Support\ApiResponse::serverError('Login failed', $e);
        }
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        try {
            $user = auth()->user();
            $token = $user->token();

            // Revoke token
            $token->revoke();

            // Invalidate token cache in all services
            // Note: In a production system, you'd use an event/message queue
            // For now, we'll publish an event that other services can subscribe to
            $this->invalidateTokenCache($token->id);

            return \App\Support\ApiResponse::success(null, 'Successfully logged out');
        } catch (\Exception $e) {
            return \App\Support\ApiResponse::serverError('Logout failed', $e);
        }
    }

    /**
     * Invalidate token cache across services
     *
     * @param string $tokenId
     * @return void
     */
    protected function invalidateTokenCache(string $tokenId): void
    {
        // Publish event to Redis for cache invalidation
        // Other services should subscribe to this event
        try {
            \Illuminate\Support\Facades\Redis::publish('token.revoked', json_encode([
                'token_id' => $tokenId,
                'user_id' => auth()->id(),
                'timestamp' => now()->toISOString(),
            ]));
        } catch (\Exception $e) {
            // Log but don't fail logout if cache invalidation fails
            \Illuminate\Support\Facades\Log::warning('Failed to publish token revocation event', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Refresh a token.
     *
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        try {
            $user = auth()->user();
            $user->token()->revoke();
            $newToken = $user->createToken('Personal Access Token')->accessToken;

            return \App\Support\ApiResponse::success([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'token' => $newToken,
                'token_type' => 'Bearer',
            ], 'Token refreshed successfully');
        } catch (\Exception $e) {
            return \App\Support\ApiResponse::serverError('Token refresh failed', $e);
        }
    }

    /**
     * Get the authenticated User with roles and permissions.
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        try {
            $user = auth('api')->user();

            if (!$user) {
                return \App\Support\ApiResponse::unauthorized('User not authenticated');
            }

            // Load user with roles and permissions
            $userWithRoles = User::with('roles', 'roles.permissions')->find($user->id);

            // Extract all permissions from all roles
            $permissions = $userWithRoles->roles
                ->pluck('permissions')
                ->flatten()
                ->unique('id')
                ->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'description' => $permission->description
                    ];
                });

            return \App\Support\ApiResponse::success([
                'user' => [
                    'id' => $userWithRoles->id,
                    'name' => $userWithRoles->name,
                    'email' => $userWithRoles->email,
                    'email_verified_at' => $userWithRoles->email_verified_at,
                    'created_at' => $userWithRoles->created_at,
                    'updated_at' => $userWithRoles->updated_at,
                ],
                'roles' => $userWithRoles->roles->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description
                    ];
                }),
                'permissions' => $permissions
            ], 'User profile retrieved successfully');
        } catch (\Exception $e) {
            return \App\Support\ApiResponse::serverError('Failed to retrieve user profile', $e);
        }
    }
}
