<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Player;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class StatisticsController extends Controller
{
    /**
     * Get statistics for team service
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $statistics = [
                'teams' => $this->getTeamStatistics(),
                'players' => $this->getPlayerStatistics(),
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
     * Get team statistics
     *
     * @return array
     */
    private function getTeamStatistics(): array
    {
        try {
            $total = Team::count();

            // Count teams with players
            $teamsWithPlayers = Team::has('players')->count();

            // Count teams without players
            $teamsWithoutPlayers = $total - $teamsWithPlayers;

            return [
                'total' => $total,
                'with_players' => $teamsWithPlayers,
                'without_players' => $teamsWithoutPlayers,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting team statistics: ' . $e->getMessage());
            return ['total' => 0, 'with_players' => 0, 'without_players' => 0, 'error' => 'Unable to retrieve team statistics'];
        }
    }

    /**
     * Get player statistics
     *
     * @return array
     */
    private function getPlayerStatistics(): array
    {
        try {
            $total = Player::count();

            // Count players by position if position field exists
            $positions = Player::selectRaw('position, COUNT(*) as count')
                ->groupBy('position')
                ->pluck('count', 'position')
                ->toArray();

            return [
                'total' => $total,
                'by_position' => $positions,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting player statistics: ' . $e->getMessage());
            return ['total' => 0, 'by_position' => [], 'error' => 'Unable to retrieve player statistics'];
        }
    }
}
