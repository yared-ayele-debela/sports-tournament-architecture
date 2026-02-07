<?php

namespace App\Http\Controllers\Admin\Referee;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use App\Models\MatchEvent;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class MatchEventController extends Controller
{
    /**
     * Display all events for a match
     */
    public function index(MatchModel $match)
    {
        $this->checkPermission('manage_my_matches');


        // Authorization: Check if the match belongs to the authenticated referee
        if ($match->referee_id !== Auth::id()) {
            abort(403, 'You are not authorized to access this match.');
        }

        $events = $match->matchEvents()
            ->with(['player', 'team'])
            ->orderBy('minute', 'asc')
            ->get();

        return view('admin.referee.events.index', compact('match', 'events'));
    }

    /**
     * Store a new match event
     */
    public function store(Request $request, MatchModel $match)
    {
        // Authorization: Check if the match belongs to the authenticated referee
        if ($match->referee_id !== Auth::id()) {
            abort(403, 'You are not authorized to manage this match.');
        }

        $validated = $request->validate([
            'player_id' => 'nullable|exists:players,id',
            'team_id' => 'required|exists:teams,id',
            'event_type' => 'required|in:goal,yellow_card,red_card,substitution',
            'minute' => 'required|integer|min:1|max:120',
            'description' => 'nullable|string|max:255'
        ]);

        DB::beginTransaction();
        try {
            $event = MatchEvent::create([
                'match_id' => $match->id,
                'player_id' => $validated['player_id'],
                'team_id' => $validated['team_id'],
                'event_type' => $validated['event_type'],
                'minute' => $validated['minute'],
                'description' => $validated['description'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Update match score if it's a goal
            if ($validated['event_type'] === 'goal') {
                $this->updateMatchScore($match);
            }

            DB::commit();

            return redirect()
                ->route('admin.referee.matches.show', $match)
                ->with('success', 'Event recorded successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->route('admin.referee.matches.show', $match)
                ->with('error', 'Failed to record event: ' . $e->getMessage());
        }
    }

    /**
     * Update a match event
     */
    public function update(Request $request, MatchModel $match, MatchEvent $event)
    {
        // Authorization: Check if the match belongs to the authenticated referee
        if ($match->referee_id !== Auth::id()) {
            abort(403, 'You are not authorized to manage this match.');
        }

        // Additional check: Ensure the event belongs to this match
        if ($event->match_id !== $match->id) {
            abort(403, 'Event does not belong to this match.');
        }

        $validated = $request->validate([
            'minute' => 'sometimes|integer|min:1|max:120',
            'description' => 'sometimes|nullable|string|max:255'
        ]);

        $event->update($validated);

        return redirect()
            ->route('admin.referee.matches.show', $match)
            ->with('success', 'Event updated successfully');
    }

    /**
     * Delete a match event
     */
    public function destroy(MatchModel $match, MatchEvent $event)
    {
        // Authorization: Check if the match belongs to the authenticated referee
        if ($match->referee_id !== Auth::id()) {
            abort(403, 'You are not authorized to manage this match.');
        }

        // Additional check: Ensure the event belongs to this match
        if ($event->match_id !== $match->id) {
            abort(403, 'Event does not belong to this match.');
        }

        $event->delete();

        // Update match score if it was a goal
        if ($event->event_type === 'goal') {
            $this->updateMatchScore($match);
        }

        return redirect()
            ->route('admin.referee.matches.show', $match)
            ->with('success', 'Event deleted successfully');
    }

    /**
     * Update match score based on events
     */
    private function updateMatchScore(MatchModel $match): void
    {
        $homeGoals = $match->matchEvents()
            ->where('team_id', $match->home_team_id)
            ->where('event_type', 'goal')
            ->count();

        $awayGoals = $match->matchEvents()
            ->where('team_id', $match->away_team_id)
            ->where('event_type', 'goal')
            ->count();

        $match->update([
            'home_score' => $homeGoals,
            'away_score' => $awayGoals
        ]);
    }
}
