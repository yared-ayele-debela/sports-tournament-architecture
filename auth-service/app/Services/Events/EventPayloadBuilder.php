<?php

namespace App\Services\Events;

use App\Models\User;
use Carbon\Carbon;

/**
 * Event Payload Builder for Auth Service
 * 
 * Helper class to build standardized event payloads
 */
class EventPayloadBuilder
{
    /**
     * Build user registered event payload
     *
     * @param User $user
     * @param array $registrationData
     * @return array
     */
    public static function userRegistered(User $user, array $registrationData = []): array
    {
        return [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'username' => $user->username ?? null,
            'roles' => $user->getRoleNames()->toArray(),
            'registration_method' => $registrationData['method'] ?? 'email',
            'ip_address' => $registrationData['ip_address'] ?? null,
            'user_agent' => $registrationData['user_agent'] ?? null,
            'registered_at' => $user->created_at->toISOString(),
            'email_verified' => $user->hasVerifiedEmail(),
        ];
    }

    /**
     * Build user role assigned event payload
     *
     * @param User $user
     * @param string $role
     * @param string|null $assignedBy
     * @return array
     */
    public static function userRoleAssigned(User $user, string $role, ?string $assignedBy = null): array
    {
        return [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role_assigned' => $role,
            'assigned_by' => $assignedBy,
            'assigned_at' => now()->toISOString(),
            'previous_roles' => $user->getRoleNames()->diff([$role])->toArray(),
            'current_roles' => $user->getRoleNames()->toArray(),
        ];
    }

    /**
     * Build user role removed event payload
     *
     * @param User $user
     * @param string $role
     * @param string|null $removedBy
     * @return array
     */
    public static function userRoleRemoved(User $user, string $role, ?string $removedBy = null): array
    {
        return [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role_removed' => $role,
            'removed_by' => $removedBy,
            'removed_at' => now()->toISOString(),
            'previous_roles' => $user->getRoleNames()->merge([$role])->toArray(),
            'current_roles' => $user->getRoleNames()->toArray(),
        ];
    }

    /**
     * Build user logged in event payload
     *
     * @param User $user
     * @param array $loginData
     * @return array
     */
    public static function userLoggedIn(User $user, array $loginData = []): array
    {
        return [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'login_method' => $loginData['method'] ?? 'password',
            'ip_address' => $loginData['ip_address'] ?? null,
            'user_agent' => $loginData['user_agent'] ?? null,
            'login_at' => now()->toISOString(),
            'session_id' => $loginData['session_id'] ?? null,
            'remember_me' => $loginData['remember_me'] ?? false,
            'roles' => $user->getRoleNames()->toArray(),
        ];
    }

    /**
     * Build user logged out event payload
     *
     * @param User $user
     * @param array $logoutData
     * @return array
     */
    public static function userLoggedOut(User $user, array $logoutData = []): array
    {
        return [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'logout_at' => now()->toISOString(),
            'session_id' => $logoutData['session_id'] ?? null,
            'ip_address' => $logoutData['ip_address'] ?? null,
            'reason' => $logoutData['reason'] ?? 'manual',
        ];
    }

    /**
     * Build user updated event payload
     *
     * @param User $user
     * @param array $changes
     * @param string|null $updatedBy
     * @return array
     */
    public static function userUpdated(User $user, array $changes, ?string $updatedBy = null): array
    {
        return [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'updated_fields' => array_keys($changes),
            'changes' => $changes,
            'updated_by' => $updatedBy,
            'updated_at' => now()->toISOString(),
            'previous_values' => $changes['previous'] ?? [],
            'new_values' => $changes['new'] ?? [],
        ];
    }

    /**
     * Build user deleted event payload
     *
     * @param User $user
     * @param string|null $deletedBy
     * @return array
     */
    public static function userDeleted(User $user, ?string $deletedBy = null): array
    {
        return [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'deleted_by' => $deletedBy,
            'deleted_at' => now()->toISOString(),
            'roles' => $user->getRoleNames()->toArray(),
            'was_email_verified' => $user->hasVerifiedEmail(),
            'account_age_days' => $user->created_at->diffInDays(now()),
        ];
    }

    /**
     * Build password changed event payload
     *
     * @param User $user
     * @param array $passwordData
     * @return array
     */
    public static function passwordChanged(User $user, array $passwordData = []): array
    {
        return [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'changed_at' => now()->toISOString(),
            'ip_address' => $passwordData['ip_address'] ?? null,
            'user_agent' => $passwordData['user_agent'] ?? null,
            'method' => $passwordData['method'] ?? 'user_request',
            'forced_change' => $passwordData['forced_change'] ?? false,
        ];
    }

    /**
     * Build email verified event payload
     *
     * @param User $user
     * @param array $verificationData
     * @return array
     */
    public static function emailVerified(User $user, array $verificationData = []): array
    {
        return [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'verified_at' => now()->toISOString(),
            'verification_method' => $verificationData['method'] ?? 'link',
            'ip_address' => $verificationData['ip_address'] ?? null,
            'user_agent' => $verificationData['user_agent'] ?? null,
        ];
    }

    /**
     * Build token revoked event payload
     *
     * @param User $user
     * @param array $tokenData
     * @return array
     */
    public static function tokenRevoked(User $user, array $tokenData = []): array
    {
        return [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'token_id' => $tokenData['token_id'] ?? null,
            'revoked_at' => now()->toISOString(),
            'reason' => $tokenData['reason'] ?? 'user_request',
            'revoked_by' => $tokenData['revoked_by'] ?? 'system',
            'ip_address' => $tokenData['ip_address'] ?? null,
        ];
    }

    /**
     * Build login attempt event payload (failed/successful)
     *
     * @param array $attemptData
     * @return array
     */
    public static function loginAttempt(array $attemptData): array
    {
        return [
            'email' => $attemptData['email'] ?? null,
            'ip_address' => $attemptData['ip_address'] ?? null,
            'user_agent' => $attemptData['user_agent'] ?? null,
            'attempted_at' => now()->toISOString(),
            'success' => $attemptData['success'] ?? false,
            'failure_reason' => $attemptData['failure_reason'] ?? null,
            'user_id' => $attemptData['user_id'] ?? null,
            'login_method' => $attemptData['method'] ?? 'password',
        ];
    }

    /**
     * Build security alert event payload
     *
     * @param User $user
     * @param string $alertType
     * @param array $alertData
     * @return array
     */
    public static function securityAlert(User $user, string $alertType, array $alertData = []): array
    {
        return [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'alert_type' => $alertType,
            'alert_data' => $alertData,
            'alert_at' => now()->toISOString(),
            'ip_address' => $alertData['ip_address'] ?? null,
            'user_agent' => $alertData['user_agent'] ?? null,
            'severity' => $alertData['severity'] ?? 'medium',
        ];
    }

    /**
     * Sanitize sensitive data for event payloads
     *
     * @param array $data
     * @return array
     */
    public static function sanitizePayload(array $data): array
    {
        $sensitiveFields = [
            'password', 'password_confirmation', 'token', 'secret',
            'api_key', 'access_token', 'refresh_token', 'csrf_token'
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }
}
