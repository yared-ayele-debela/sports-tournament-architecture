<?php

namespace App\Http\Controllers\Admin\Referee;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use App\Models\MatchEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class MatchController extends Controller
{
    /**
     * Display referee dashboard
     */
    public function dashboard()
    {
        // Gate::authorize('manage_my_matches');
        $this->checkPermission('manage_my_matches');

        $user = Auth::user();
        $matches = MatchModel::where('referee_id', $user->id)
            ->where('status', 'in_progress')
            ->with(['tournament', 'homeTeam', 'awayTeam', 'venue', 'referee'])
            ->select([
                'id', 'tournament_id', 'home_team_id', 'away_team_id',
                'venue_id', 'referee_id', 'match_date', 'status', 'round_number',
                'home_score', 'away_score', 'current_minute'
            ])
            ->orderBy('match_date', 'asc')
            ->get();

        return view('admin.referee.dashboard', compact('matches'));
    }

    /**
     * Display listing of matches assigned to referee
     */
    public function index()
    {
        $this->checkPermission('manage_my_matches');

        $user = Auth::user();
        $matches = MatchModel::where('referee_id', $user->id)
            ->with(['tournament', 'homeTeam', 'awayTeam', 'venue', 'referee'])
            ->orderBy('match_date', 'desc')
            ->paginate(15);

        return view('admin.referee.matches.index', compact('matches'));
    }

    /**
     * Display the specified match
     */
    public function show(MatchModel $match)
    {
        $this->checkPermission('manage_my_matches');

        // Authorization: Check if the match belongs to the authenticated referee
        if ($match->referee_id !== Auth::id()) {
            abort(403, 'You are not authorized to access this match.');
        }

        $match->load([
            'tournament',
            'homeTeam.players',
            'awayTeam.players',
            'venue',
            'matchReport',
            'referee',
            'matchEvents.player',
            'matchEvents.team'
        ]);
        // dd($match);

        return view('admin.referee.matches.show', compact('match'));
    }

    /**
     * Start the match
     */
    public function start(MatchModel $match, Request $request)
    {
        // Authorization: Check if the match belongs to the authenticated referee
        if ($match->referee_id !== Auth::id()) {
            abort(403, 'You are not authorized to manage this match.');
        }

        $match->update([
            'status' => 'in_progress',
            'current_minute' => 0
        ]);

        return redirect()
            ->route('admin.referee.matches.show', $match)
            ->with('success', 'Match started successfully.');
    }

    /**
     * Pause the match
     */
    public function pause(MatchModel $match, Request $request)
    {
        // Authorization: Check if the match belongs to the authenticated referee
        if ($match->referee_id !== Auth::id()) {
            abort(403, 'You are not authorized to manage this match.');
        }

        $match->update(['status' => 'paused']);

        return redirect()
            ->route('admin.referee.matches.show', $match)
            ->with('success', 'Match paused successfully.');
    }

    /**
     * End the match
     */
    public function end(MatchModel $match, Request $request)
    {
        // Authorization: Check if the match belongs to the authenticated referee
        if ($match->referee_id !== Auth::id()) {
            abort(403, 'You are not authorized to manage this match.');
        }

        $match->update(['status' => 'completed']);

        return redirect()
            ->route('admin.referee.matches.show', $match)
            ->with('success', 'Match ended successfully.');
    }

    /**
     * Update match score
     */
    public function updateScore(MatchModel $match, Request $request)
    {
        // Authorization: Check if the match belongs to the authenticated referee
        if ($match->referee_id !== Auth::id()) {
            abort(403, 'You are not authorized to manage this match.');
        }

        $validated = $request->validate([
            'home_score' => 'required|integer|min:0',
            'away_score' => 'required|integer|min:0'
        ]);

        $match->update($validated);

        return redirect()
            ->route('admin.referee.matches.show', $match)
            ->with('success', 'Score updated successfully.');
    }

    /**
     * Update current match minute
     */
    public function updateMinute(MatchModel $match, Request $request)
    {
        // Authorization: Check if the match belongs to the authenticated referee
        if ($match->referee_id !== Auth::id()) {
            abort(403, 'You are not authorized to manage this match.');
        }

        $validated = $request->validate([
            'current_minute' => 'required|integer|min:0|max:120'
        ]);

        $match->update($validated);

        return redirect()
            ->route('admin.referee.matches.show', $match)
            ->with('success', 'Match minute updated successfully.');
    }
}
