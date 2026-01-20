<?php

namespace App\Helpers;

class AuthHelper
{
    public static function isAdmin(): bool
    {
        $user = request('authenticated_user');
        $roles = request('user_roles', []);
        
        // Check if user has Administrator role
        if (!empty($roles)) {
            foreach ($roles as $role) {
                if (is_array($role) && $role['name'] === 'Administrator') {
                    return true;
                }
            }
        }
        
        return false;
    }

    public static function isCoach(): bool
    {
        $user = request('authenticated_user');
        $roles = request('user_roles', []);
        
        // Check if user has Coach role
        if (!empty($roles)) {
            foreach ($roles as $role) {
                if (is_array($role) && $role['name'] === 'Coach') {
                    return true;
                }
            }
        }
        
        return false;
    }

    public static function canManageTeam($teamId): bool
    {
        if (self::isAdmin()) {
            return true;
        }

        if (self::isCoach()) {
            $team = \App\Models\Team::find($teamId);
            return $team && $team->isCoach(self::getCurrentUserId());
        }

        return false;
    }

    public static function getCurrentUserId(): ?int
    {
        $user = request('authenticated_user');
        return $user['id'] ?? null;
    }
}
