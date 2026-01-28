<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\Team;
use App\Models\MatchModel;
use App\Models\Player;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index()
    {
        // Cache heavy queries for 5 minutes
        $cacheTime = 300;

        // Enhanced Summary Statistics
        $stats = Cache::remember('dashboard_stats', $cacheTime, function () {
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

        // Match Status Chart Data
        $matchStatusData = Cache::remember('match_status_chart', $cacheTime, function () {
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

        // Matches Per Day (Last 7 Days)
        $dailyMatchesData = Cache::remember('daily_matches_chart', $cacheTime, function () {
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

        // Recent Activity Panels
        $recentMatches = Cache::remember('recent_matches', $cacheTime, function () {
            return MatchModel::with(['homeTeam', 'awayTeam', 'venue'])
                ->orderBy('created_at', 'desc')
                ->take(5)
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

        $recentUsers = Cache::remember('recent_users', $cacheTime, function () {
            return User::orderBy('created_at', 'desc')
                ->take(5)
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

        $recentCompletedMatches = Cache::remember('recent_completed_matches', $cacheTime, function () {
            return MatchModel::with(['homeTeam', 'awayTeam', 'venue'])
                ->where('status', 'completed')
                ->orderBy('updated_at', 'desc')
                ->take(5)
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

        return view('admin.dashboard', compact(
            'stats',
            'matchStatusData',
            'dailyMatchesData',
            'recentMatches',
            'recentUsers',
            'recentCompletedMatches'
        ));
    }

    /**
     * Display the coach dashboard within admin panel.
     */
    public function coachDashboard()
    {
        $user = Auth::user();
        $teams = Team::whereHas('coaches', function($query) use ($user) {
            $query->where('user_id', $user->id);
        })->with(['tournament', 'players', 'coaches'])->get();
        
        // Get all matches for coach's teams (upcoming and recent)
        $allMatches = collect();
        foreach ($teams as $team) {
            $teamMatches = $team->matches()
                ->with(['homeTeam', 'awayTeam', 'venue'])
                ->orderBy('match_date', 'desc')
                ->get();
            $allMatches = $allMatches->merge($teamMatches);
        }
        
        // Separate upcoming and recent matches
        $upcomingMatches = $allMatches
            ->where('match_date', '>', now())
            ->sortBy('match_date')
            ->take(10);
            
        $recentMatches = $allMatches
            ->where('match_date', '<=', now())
            ->sortByDesc('match_date')
            ->take(5);
        
        return view('admin.coach.dashboard.coach-dashboard', compact('teams', 'upcomingMatches', 'recentMatches'));
    }
}
