<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\Sport;
use App\Models\Venue;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class StatisticsController extends Controller
{
    /**
     * Get statistics for tournament service
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $statistics = [
                'tournaments' => $this->getTournamentStatistics(),
                'sports' => $this->getSportStatistics(),
                'venues' => $this->getVenueStatistics(),
            ];

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
     * Get tournament statistics
     *
     * @return array
     */
    private function getTournamentStatistics(): array
    {
        try {
            $total = Tournament::count();
            $active = Tournament::where('status', 'ongoing')->count();
            $completed = Tournament::where('status', 'completed')->count();
            $draft = Tournament::where('status', 'draft')->count();

            return [
                'total' => $total,
                'active' => $active,
                'completed' => $completed,
                'draft' => $draft,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting tournament statistics: ' . $e->getMessage());
            return ['total' => 0, 'active' => 0, 'completed' => 0, 'draft' => 0, 'error' => 'Unable to retrieve tournament statistics'];
        }
    }

    /**
     * Get sport statistics
     *
     * @return array
     */
    private function getSportStatistics(): array
    {
        try {
            $total = Sport::count();
            $teamBased = Sport::where('team_based', true)->count();
            $individual = Sport::where('team_based', false)->count();

            return [
                'total' => $total,
                'team_based' => $teamBased,
                'individual' => $individual,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting sport statistics: ' . $e->getMessage());
            return ['total' => 0, 'team_based' => 0, 'individual' => 0, 'error' => 'Unable to retrieve sport statistics'];
        }
    }

    /**
     * Get venue statistics
     *
     * @return array
     */
    private function getVenueStatistics(): array
    {
        try {
            $total = Venue::count();
            $totalCapacity = Venue::sum('capacity');
            $averageCapacity = $total > 0 ? round($totalCapacity / $total, 0) : 0;

            return [
                'total' => $total,
                'total_capacity' => $totalCapacity,
                'average_capacity' => $averageCapacity,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting venue statistics: ' . $e->getMessage());
            return ['total' => 0, 'total_capacity' => 0, 'average_capacity' => 0, 'error' => 'Unable to retrieve venue statistics'];
        }
    }
}
