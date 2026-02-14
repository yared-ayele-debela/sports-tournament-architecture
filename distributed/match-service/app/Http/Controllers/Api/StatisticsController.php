<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchGame;
use App\Support\ApiResponse;
use App\Helpers\AuthHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class StatisticsController extends Controller
{
    /**
     * Get statistics for match service
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $statistics = [
                'matches' => $this->getMatchStatistics(),
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
     * Get match statistics
     *
     * @return array
     */
    private function getMatchStatistics(): array
    {
        try {
            $total = MatchGame::count();
            $scheduled = MatchGame::where('status', 'scheduled')->count();
            $inProgress = MatchGame::where('status', 'in_progress')->count();
            $completed = MatchGame::where('status', 'completed')->count();
            $cancelled = MatchGame::where('status', 'cancelled')->count();

            // Calculate average goals per match
            $completedMatches = MatchGame::where('status', 'completed')
                ->whereNotNull('home_score')
                ->whereNotNull('away_score')
                ->get();

            $totalGoals = $completedMatches->sum(function ($match) {
                return ($match->home_score ?? 0) + ($match->away_score ?? 0);
            });

            $averageGoals = $completedMatches->count() > 0
                ? round($totalGoals / $completedMatches->count(), 2)
                : 0;

            return [
                'total' => $total,
                'scheduled' => $scheduled,
                'in_progress' => $inProgress,
                'completed' => $completed,
                'cancelled' => $cancelled,
                'average_goals_per_match' => $averageGoals,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting match statistics: ' . $e->getMessage());
            return [
                'total' => 0,
                'scheduled' => 0,
                'in_progress' => 0,
                'completed' => 0,
                'cancelled' => 0,
                'average_goals_per_match' => 0,
                'error' => 'Unable to retrieve match statistics'
            ];
        }
    }

    /**
     * Get matches by status for chart
     *
     * @return JsonResponse
     */
    public function matchesByStatus(): JsonResponse
    {
        try {
            $scheduled = MatchGame::where('status', 'scheduled')->count();
            $inProgress = MatchGame::where('status', 'in_progress')->count();
            $completed = MatchGame::where('status', 'completed')->count();
            $cancelled = MatchGame::where('status', 'cancelled')->count();

            $data = [
                ['status' => 'Scheduled', 'count' => $scheduled],
                ['status' => 'Ongoing', 'count' => $inProgress],
                ['status' => 'Completed', 'count' => $completed],
                ['status' => 'Cancelled', 'count' => $cancelled],
            ];

            return ApiResponse::success($data, 'Matches by status retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error getting matches by status: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to retrieve matches by status', $e);
        }
    }

    /**
     * Get match statistics by status for coach's teams
     *
     * @return JsonResponse
     */
    public function coachMatchesByStatus(): JsonResponse
    {
        try {
            // Get coach's team IDs
            $teamIds = AuthHelper::getCoachTeamIds();

            if (empty($teamIds)) {
                // Return zeros if coach has no teams
                return ApiResponse::success([
                    'scheduled' => 0,
                    'in_progress' => 0,
                    'completed' => 0,
                    'cancelled' => 0,
                    'total' => 0,
                ], 'Coach match statistics retrieved successfully');
            }

            // Query matches for coach's teams
            $query = MatchGame::where(function ($q) use ($teamIds) {
                $q->whereIn('home_team_id', $teamIds)
                  ->orWhereIn('away_team_id', $teamIds);
            });

            $scheduled = (clone $query)->where('status', 'scheduled')->count();
            $inProgress = (clone $query)->where('status', 'in_progress')->count();
            $completed = (clone $query)->where('status', 'completed')->count();
            $cancelled = (clone $query)->where('status', 'cancelled')->count();
            $total = (clone $query)->count();

            $data = [
                'scheduled' => $scheduled,
                'in_progress' => $inProgress,
                'completed' => $completed,
                'cancelled' => $cancelled,
                'total' => $total,
            ];

            return ApiResponse::success($data, 'Coach match statistics retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error getting coach match statistics: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to retrieve coach match statistics', $e);
        }
    }
}
