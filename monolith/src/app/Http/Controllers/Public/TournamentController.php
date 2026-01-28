<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\Match;
use App\Models\Standing;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TournamentController extends Controller
{
    /**
     * Display a listing of all tournaments
     */
    public function index(Request $request): View
    {
        $query = Tournament::with(['sport', 'teams'])
                ->orderBy('start_date', 'desc');
        
        // Filter by sport
        if ($request->filled('sport')) {
            $query->whereHas('sport', function ($q) use ($request) {
                $q->where('name', $request->sport);
            });
        }
        
        // Filter by status
        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('start_date', '<=', now())
                          ->where('end_date', '>=', now());
                    break;
                case 'upcoming':
                    $query->where('start_date', '>', now());
                    break;
                case 'completed':
                    $query->where('end_date', '<', now());
                    break;
            }
        }
        
        $tournaments = $query->paginate(12);

        // Get available sports for filtering
        $sports = \App\Models\Sport::orderBy('name')->get();

        return view('public.tournaments.index', compact('tournaments', 'sports'));
    }

    /**
     * Display the specified tournament
     */
    public function show(Tournament $tournament): View
    {
        $tournament->load([
            'sport',
            'teams.players',
            'matches.homeTeam',
            'matches.awayTeam',
            'matches.venue',
            'standings.team'
        ]);

        // Get recent matches
        $recentMatches = $tournament->matches()
            ->with(['homeTeam', 'awayTeam', 'venue'])
            ->orderBy('match_date', 'desc')
            ->take(5)
            ->get();

        // Get current standings
        $standings = $tournament->standings()
            ->with('team')
            ->orderBy('position')
            ->get();

        return view('public.tournaments.show', compact('tournament', 'recentMatches', 'standings'));
    }

    /**
     * Display tournament matches
     */
    public function matches(Request $request, Tournament $tournament): View
    {
        $query = $tournament->matches()
                ->with(['homeTeam', 'awayTeam', 'venue']);
            
        // Filter by round
        if ($request->filled('round')) {
            $query->where('round_number', $request->round);
        }
        
        // Filter by team
        if ($request->filled('team')) {
            $query->where(function ($q) use ($request) {
                $q->where('home_team_id', $request->team)
                  ->orWhere('away_team_id', $request->team);
            });
        }
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        $matches = $query->orderBy('match_date', 'desc')->paginate(15);

        $rounds = $tournament->matches()
            ->select('round_number')
            ->distinct()
            ->orderBy('round_number')
            ->pluck('round_number');

        $teams = $tournament->teams()
            ->orderBy('name')
            ->get();

        return view('public.tournaments.matches', compact('tournament', 'matches', 'rounds', 'teams'));
    }

    /**
     * Display tournament standings
     */
    public function standings(Tournament $tournament): View
    {
        $standings = $tournament->standings()
            ->with('team')
            ->orderBy('position')
            ->get();

        return view('public.tournaments.standings', compact('tournament', 'standings'));
    }

    /**
     * Display tournament teams
     */
    public function teams(Tournament $tournament): View
    {
        $teams = $tournament->teams()
                ->withCount('players')
                ->orderBy('name')
                ->get();

        return view('public.tournaments.teams', compact('tournament', 'teams'));
    }
}
