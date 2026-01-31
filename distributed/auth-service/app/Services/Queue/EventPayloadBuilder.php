<?php

namespace App\Services\Queue;

use App\Models\User;
use Carbon\Carbon;

/**
 * Event Payload Builder for Auth Service Queue Events
 * 
 * Provides helper methods to build standardized event payloads
 */
class EventPayloadBuilder
{
    /**
     * Build payload for user.registered event
     *
     * @param User $user
     * @param array $metadata Additional metadata (ip_address, user_agent, method, etc.)
     * @return array
     */
    public static function userRegistered(User $user, array $metadata = []): array
    {
        return [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'username' => $user->email, // Using email as username if no separate username field
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'registered_at' => $user->created_at->toIso8601String(),
            'method' => $metadata['method'] ?? 'email',
            'ip_address' => $metadata['ip_address'] ?? null,
            'user_agent' => $metadata['user_agent'] ?? null,
        ];
    }

    /**
     * Build payload for user.role.assigned event
     *
     * @param User $user
     * @param string|int $roleId Role ID or role name
     * @param int|string|null $assignedBy User ID who assigned the role (null for system)
     * @param array $metadata Additional metadata
     * @return array
     */
    public static function userRoleAssigned(User $user, $roleId, $assignedBy = null, array $metadata = []): array
    {
        return [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_name' => $user->name,
            'role_id' => is_numeric($roleId) ? (int) $roleId : null,
            'role_name' => is_string($roleId) ? $roleId : null,
            'assigned_by' => $assignedBy,
            'assigned_at' => Carbon::now()->toIso8601String(),
            'ip_address' => $metadata['ip_address'] ?? null,
            'user_agent' => $metadata['user_agent'] ?? null,
        ];
    }

    /**
     * Build payload for user.logged.in event
     *
     * @param User $user
     * @param array $metadata Additional metadata (ip_address, user_agent, method, remember_me, etc.)
     * @return array
     */
    public static function userLoggedIn(User $user, array $metadata = []): array
    {
        return [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'logged_in_at' => Carbon::now()->toIso8601String(),
            'method' => $metadata['method'] ?? 'password',
            'ip_address' => $metadata['ip_address'] ?? null,
            'user_agent' => $metadata['user_agent'] ?? null,
            'remember_me' => $metadata['remember_me'] ?? false,
        ];
    }
}
