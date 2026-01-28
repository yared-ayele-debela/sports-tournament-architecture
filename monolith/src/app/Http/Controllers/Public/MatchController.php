<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Match;
use App\Models\MatchEvent;
use App\Models\MatchModel;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MatchController extends Controller
{
    /**
     * Display a listing of recent matches
     */
    public function index(Request $request): View
    {
        $query = MatchModel::with(['homeTeam', 'awayTeam', 'venue', 'tournament'])
                ->orderBy('match_date', 'desc');
        
        // Filter by tournament
        if ($request->filled('tournament')) {
            $query->where('tournament_id', $request->tournament);
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
        
        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('match_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('match_date', '<=', $request->date_to);
        }
        
        $matches = $query->paginate(20);

        // Get available tournaments for filtering
        $tournaments = \App\Models\Tournament::orderBy('name')->get();

        return view('public.matches.index', compact('matches', 'tournaments'));
    }

    /**
     * Display the specified match
     */
    public function show(MatchModel $match): View
    {
        $match->load([
            'homeTeam.players',
            'awayTeam.players',
            'venue',
            'tournament',
            'matchEvents.player',
            'matchEvents.team'
        ]);

        // Get match statistics
        $homeEvents = $match->matchEvents->where('team_id', $match->home_team_id);
        $awayEvents = $match->matchEvents->where('team_id', $match->away_team_id);

        $stats = [
            'home' => [
                'goals' => $homeEvents->where('event_type', 'goal')->count(),
                'yellow_cards' => $homeEvents->where('event_type', 'yellow_card')->count(),
                'red_cards' => $homeEvents->where('event_type', 'red_card')->count(),
                'substitutions' => $homeEvents->where('event_type', 'substitution')->count(),
            ],
            'away' => [
                'goals' => $awayEvents->where('event_type', 'goal')->count(),
                'yellow_cards' => $awayEvents->where('event_type', 'yellow_card')->count(),
                'red_cards' => $awayEvents->where('event_type', 'red_card')->count(),
                'substitutions' => $awayEvents->where('event_type', 'substitution')->count(),
            ],
        ];

        // Get head-to-head history
        $headToHead = MatchModel::with(['homeTeam', 'awayTeam', 'tournament'])
            ->where(function ($query) use ($match) {
                $query->where(function ($q) use ($match) {
                    $q->where('home_team_id', $match->home_team_id)
                      ->where('away_team_id', $match->away_team_id);
                })->orWhere(function ($q) use ($match) {
                    $q->where('home_team_id', $match->away_team_id)
                      ->where('away_team_id', $match->home_team_id);
                });
            })
            ->where('id', '!=', $match->id)
            ->orderBy('match_date', 'desc')
            ->take(5)
            ->get();

        return view('public.matches.show', compact('match', 'stats', 'headToHead'));
    }
}
