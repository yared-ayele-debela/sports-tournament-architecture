<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Display the public home page
     */
    public function index(Request $request): View
    {
        // Get featured tournaments
        $featuredTournaments = \App\Models\Tournament::with(['sport', 'teams'])
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->orderBy('start_date', 'desc')
                ->take(3)
                ->get();

        // Get recent matches
        $recentMatches = \App\Models\MatchModel::with(['homeTeam', 'awayTeam', 'venue', 'tournament'])
                ->orderBy('match_date', 'desc')
                ->take(6)
                ->get();

        // Get top teams by performance
        $topTeams = \App\Models\Team::with(['tournament'])
                ->withCount(['homeMatches', 'awayMatches'])
                ->get()
                ->map(function ($team) {
                    $totalMatches = $team->home_matches_count + $team->away_matches_count;
                    $wins = \App\Models\MatchModel::where(function ($query) use ($team) {
                        $query->where('home_team_id', $team->id)
                              ->where('home_score', '>', 'away_score');
                    })->orWhere(function ($query) use ($team) {
                        $query->where('away_team_id', $team->id)
                              ->where('away_score', '>', 'home_score');
                    })->count();

                    return [
                        'team' => $team,
                        'total_matches' => $totalMatches,
                        'wins' => $wins,
                        'win_rate' => $totalMatches > 0 ? round(($wins / $totalMatches) * 100, 1) : 0,
                    ];
                })
                ->sortByDesc('win_rate')
                ->take(6)
                ->pluck('team');

        // Get statistics
        $stats = [
            'total_tournaments' => \App\Models\Tournament::count(),
            'active_tournaments' => \App\Models\Tournament::where('start_date', '<=', now())
                ->where('end_date', '>=', now())->count(),
            'total_teams' => \App\Models\Team::count(),
            'total_matches' => \App\Models\MatchModel::count(),
            'total_players' => \App\Models\Player::count(),
        ];

        return view('public.home.index', compact('featuredTournaments', 'recentMatches', 'topTeams', 'stats'));
    }
}
