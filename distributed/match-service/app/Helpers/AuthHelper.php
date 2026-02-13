<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    public static function getCurrentUserId(): ?int
    {
        $user = request('authenticated_user');
        return $user['id'] ?? null;
    }

    /**
     * Get team IDs for the current coach
     *
     * @return array
     */
    public static function getCoachTeamIds(): array
    {
        if (!self::isCoach()) {
            return [];
        }

        $userId = self::getCurrentUserId();
        if (!$userId) {
            return [];
        }

        try {
            $teamServiceUrl = config('services.team_service.url', env('TEAM_SERVICE_URL', 'http://team-service:8003'));
            $token = request()->bearerToken();

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$token}",
                'Accept' => 'application/json',
            ])->get("{$teamServiceUrl}/api/teams", [
                'per_page' => 100
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $teams = $data['data'] ?? [];

                if (is_array($teams)) {
                    return array_column($teams, 'id');
                }
            }

            Log::warning('Failed to fetch coach teams', [
                'user_id' => $userId,
                'response_status' => $response->status(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Exception while fetching coach teams', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
