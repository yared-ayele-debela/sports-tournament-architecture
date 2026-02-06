<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\Team;
use App\Models\MatchModel;
use App\Models\Player;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * Get summary statistics for admin dashboard
     */
    public function getAdminStatistics(int $cacheTime = 300): array
    {
        return Cache::remember('dashboard_stats', $cacheTime, function () {
            return [
                'tournaments' => Tournament::count(),
                'active_tournaments' => Tournament::where('status', 'active')->count(),
                'teams' => Team::count(),
                'matches' => MatchModel::count(),
                'completed_matches' => MatchModel::where('status', 'completed')->count(),
                'players' => Player::count(),
                'venues' => Venue::count(),
                'users' => User::count(),
                'referees' => User::whereHas('roles', function ($query) {
                    $query->where('name', 'referee');
                })->count(),
            ];
        });
    }

    /**
     * Get match status chart data
     */
    public function getMatchStatusChartData(int $cacheTime = 300): array
    {
        return Cache::remember('match_status_chart', $cacheTime, function () {
            $statusCounts = MatchModel::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            return [
                'labels' => ['Scheduled', 'In Progress', 'Completed', 'Cancelled'],
                'data' => [
                    $statusCounts['scheduled'] ?? 0,
                    $statusCounts['in_progress'] ?? 0,
                    $statusCounts['completed'] ?? 0,
                    $statusCounts['cancelled'] ?? 0,
                ],
                'colors' => ['#3B82F6', '#F59E0B', '#10B981', '#EF4444'],
            ];
        });
    }

    /**
     * Get daily matches chart data (last 7 days)
     */
    public function getDailyMatchesChartData(int $cacheTime = 300): array
    {
        return Cache::remember('daily_matches_chart', $cacheTime, function () {
            $dates = collect();
            $counts = collect();

            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $dates->push(now()->subDays($i)->format('M j'));

                $count = MatchModel::whereDate('match_date', $date)->count();
                $counts->push($count);
            }

            return [
                'labels' => $dates->toArray(),
                'data' => $counts->toArray(),
            ];
        });
    }

    /**
     * Get recent matches
     */
    public function getRecentMatches(int $limit = 5, int $cacheTime = 300): Collection
    {
        return Cache::remember('recent_matches', $cacheTime, function () use ($limit) {
            return MatchModel::with(['homeTeam', 'awayTeam', 'venue'])
                ->orderBy('created_at', 'desc')
                ->take($limit)
                ->get()
                ->map(function ($match) {
                    return [
                        'id' => $match->id,
                        'teams' => $match->homeTeam->name . ' vs ' . $match->awayTeam->name,
                        'status' => $match->status,
                        'status_badge' => $match->statusBadgeClasses(),
                        'created_at' => $match->created_at->diffForHumans(),
                    ];
                });
        });
    }

    /**
     * Get recent users
     */
    public function getRecentUsers(int $limit = 5, int $cacheTime = 300): Collection
    {
        return Cache::remember('recent_users', $cacheTime, function () use ($limit) {
            return User::orderBy('created_at', 'desc')
                ->take($limit)
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'created_at' => $user->created_at->diffForHumans(),
                    ];
                });
        });
    }

    /**
     * Get recent completed matches
     */
    public function getRecentCompletedMatches(int $limit = 5, int $cacheTime = 300): Collection
    {
        return Cache::remember('recent_completed_matches', $cacheTime, function () use ($limit) {
            return MatchModel::with(['homeTeam', 'awayTeam', 'venue'])
                ->where('status', 'completed')
                ->orderBy('updated_at', 'desc')
                ->take($limit)
                ->get()
                ->map(function ($match) {
                    return [
                        'id' => $match->id,
                        'teams' => $match->homeTeam->name . ' vs ' . $match->awayTeam->name,
                        'score' => ($match->home_score ?? 0) . ' - ' . ($match->away_score ?? 0),
                        'completed_at' => $match->updated_at->diffForHumans(),
                    ];
                });
        });
    }

    /**
     * Get coach dashboard data
     */
    public function getCoachDashboardData(User $coach): array
    {
        $teams = Team::whereHas('coaches', function($query) use ($coach) {
            $query->where('user_id', $coach->id);
        })->with(['tournament', 'players', 'coaches'])->get();

        // Get all matches for coach's teams (optimized to avoid N+1)
        $teamIds = $teams->pluck('id');
        $allMatches = MatchModel::whereIn('home_team_id', $teamIds)
            ->orWhereIn('away_team_id', $teamIds)
            ->with(['homeTeam', 'awayTeam', 'venue', 'tournament'])
            ->orderBy('match_date', 'desc')
            ->get();

        // Separate upcoming and recent matches
        $upcomingMatches = $allMatches
            ->where('match_date', '>', now())
            ->sortBy('match_date')
            ->take(10);

        $recentMatches = $allMatches
            ->where('match_date', '<=', now())
            ->sortByDesc('match_date')
            ->take(5);

        return [
            'teams' => $teams,
            'upcomingMatches' => $upcomingMatches,
            'recentMatches' => $recentMatches,
        ];
    }
}
