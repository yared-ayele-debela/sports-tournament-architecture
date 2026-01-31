<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Queue\QueuePublisher;
use App\Services\Queue\EventPayloadBuilder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Laravel\Passport\Token;

class AuthController extends Controller
{
    protected QueuePublisher $queuePublisher;

    public function __construct(QueuePublisher $queuePublisher)
    {
        $this->queuePublisher = $queuePublisher;
    }
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

            // Dispatch user registered event to queue (default priority)
            $this->dispatchUserRegisteredQueueEvent($user, $request);

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

            // Dispatch user logged in event to queue (low priority - for analytics)
            $this->dispatchUserLoggedInQueueEvent($user, $request);

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

            // Token revocation events are handled via cache invalidation
            // No queue event needed for logout

            return \App\Support\ApiResponse::success(null, 'Successfully logged out');
        } catch (\Exception $e) {
            return \App\Support\ApiResponse::serverError('Logout failed', $e);
        }
    }

    /**
     * Dispatch user registered event to queue (default priority)
     *
     * @param User $user
     * @param Request $request
     * @return void
     */
    protected function dispatchUserRegisteredQueueEvent(User $user, Request $request): void
    {
        try {
            $payload = EventPayloadBuilder::userRegistered($user, [
                'method' => 'email',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $this->queuePublisher->dispatchNormal('events', $payload, 'user.registered');
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch user registered queue event', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Dispatch user logged in event to queue (low priority - for analytics)
     *
     * @param User $user
     * @param Request $request
     * @return void
     */
    protected function dispatchUserLoggedInQueueEvent(User $user, Request $request): void
    {
        try {
            $payload = EventPayloadBuilder::userLoggedIn($user, [
                'method' => 'password',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'remember_me' => $request->boolean('remember', false),
            ]);

            $this->queuePublisher->dispatchLow('events', $payload, 'user.logged.in');
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch user logged in queue event', [
                'user_id' => $user->id,
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
