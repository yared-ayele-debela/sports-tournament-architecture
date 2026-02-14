<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class StatisticsController extends Controller
{
    /**
     * Get statistics for auth service only (independent, no dependencies on other services)
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            // Cache the statistics for 5 minutes to reduce load
            $statistics = Cache::remember('auth_service_statistics', 300, function () {
                return $this->getAuthServiceStatistics();
            });

            return ApiResponse::success($statistics, 'Statistics retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving statistics: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return ApiResponse::serverError('Failed to retrieve statistics', $e);
        }
    }

    /**
     * Get auth service statistics only (users, roles, permissions)
     * This service is independent and does not call other services
     *
     * @return array
     */
    private function getAuthServiceStatistics(): array
    {
        return [
            'users' => $this->getUserStatistics(),
            'roles' => $this->getRoleStatistics(),
            'permissions' => $this->getPermissionStatistics(),
        ];
    }

    /**
     * Get user statistics from auth service
     *
     * @return array
     */
    private function getUserStatistics(): array
    {
        try {
            $totalUsers = User::count();

            return [
                'total' => $totalUsers,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting user statistics: ' . $e->getMessage());
            return ['total' => 0, 'error' => 'Unable to retrieve user statistics'];
        }
    }


    /**
     * Get role statistics from auth service
     *
     * @return array
     */
    private function getRoleStatistics(): array
    {
        try {
            $totalRoles = Role::count();

            return [
                'total' => $totalRoles,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting role statistics: ' . $e->getMessage());
            return ['total' => 0, 'error' => 'Unable to retrieve role statistics'];
        }
    }

    /**
     * Get permission statistics from auth service
     *
     * @return array
     */
    private function getPermissionStatistics(): array
    {
        try {
            $totalPermissions = Permission::count();

            return [
                'total' => $totalPermissions,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting permission statistics: ' . $e->getMessage());
            return ['total' => 0, 'error' => 'Unable to retrieve permission statistics'];
        }
    }
}
