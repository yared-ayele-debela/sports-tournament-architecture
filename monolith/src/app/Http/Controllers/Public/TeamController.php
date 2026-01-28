<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Player;
use App\Models\Match;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeamController extends Controller
{
    /**
     * Display a listing of all teams
     */
    public function index(Request $request): View
    {
        $query = Team::with(['tournament', 'players'])
            ->orderBy('name');
        
        // Filter by tournament
        if ($request->filled('tournament')) {
            $query->where('tournament_id', $request->tournament);
        }
        
        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }
        
        $teams = $query->paginate(20);

        // Get available tournaments for filtering
        $tournaments = \App\Models\Tournament::orderBy('name')->get();

        return view('public.teams.index', compact('teams', 'tournaments'));
    }

    /**
     * Display specified team
     */
    public function show(Team $team): View
    {
        $team->load([
            'tournament',
            'players',
            'homeMatches.awayTeam',
            'homeMatches.venue',
            'awayMatches.homeTeam',
            'awayMatches.venue'
        ]);

        // Get team statistics
        $allMatches = collect()
            ->merge($team->homeMatches)
            ->merge($team->awayMatches)
            ->sortBy('match_date');

        $totalMatches = $allMatches->count();
        $wins = $allMatches->filter(function ($match) use ($team) {
            return ($match->home_team_id === $team->id && $match->home_score > $match->away_score) ||
                   ($match->away_team_id === $team->id && $match->away_score > $match->home_score);
        })->count();

        $draws = $allMatches->filter(function ($match) {
            return $match->home_score === $match->away_score;
        })->count();

        $losses = $totalMatches - $wins - $draws;

        $goalsFor = $allMatches->sum(function ($match) use ($team) {
            return $match->home_team_id === $team->id ? $match->home_score : $match->away_score;
        });

        $goalsAgainst = $allMatches->sum(function ($match) use ($team) {
            return $match->home_team_id === $team->id ? $match->away_score : $match->home_score;
        });

        $stats = [
            'total_matches' => $totalMatches,
            'wins' => $wins,
            'draws' => $draws,
            'losses' => $losses,
            'goals_for' => $goalsFor,
            'goals_against' => $goalsAgainst,
            'goal_difference' => $goalsFor - $goalsAgainst,
            'win_percentage' => $totalMatches > 0 ? round(($wins / $totalMatches) * 100, 1) : 0,
        ];

        // Get recent matches
        $recentMatches = $team->homeMatches()
            ->with(['awayTeam', 'venue'])
            ->union($team->awayMatches()->with(['homeTeam', 'venue']))
            ->orderBy('match_date', 'desc')
            ->take(5)
            ->get();

        // Get top players
        $topPlayers = $team->players()
            ->withCount(['matchEvents' => function ($query) {
                $query->where('event_type', 'goal');
            }])
            ->orderBy('match_events_count', 'desc')
            ->take(5)
            ->get();

        return view('public.teams.show', compact('team', 'stats', 'recentMatches', 'topPlayers'));
    }
}
