<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\Sport;
use App\Services\MatchScheduler;
use App\Services\StandingsCalculator;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TournamentController extends Controller
{
    /**
     * Display a listing of tournaments.
     */
    public function index()
    {
        $this->checkPermission('manage_tournaments');
        $tournaments = Tournament::with('sport')
            ->orderBy('start_date', 'desc')
            ->paginate(10);

        return view('admin.tournaments.index', compact('tournaments'));
    }

    /**
     * Show the form for creating a new tournament.
     */
    public function create()
    {
        $this->checkPermission('manage_tournaments');
        $sports = Sport::orderBy('name')->get();
        return view('admin.tournaments.create', compact('sports'));
    }

    /**
     * Store a newly created tournament in storage.
     */
    public function store(Request $request)
    {
        $this->checkPermission('manage_tournaments');
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255'
            ],
            'sport_id' => [
                'required',
                'exists:sports,id',
                'integer'
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'start_date' => [
                'required',
                'date',
                'after_or_equal:today'
            ],
            'end_date' => [
                'required',
                'date',
                'after:start_date'
            ],
            'status' => [
                'required',
                'string',
                'in:draft,active,completed,cancelled'
            ],
            'max_teams' => [
                'nullable',
                'integer',
                'min:2'
            ],
            'registration_deadline' => [
                'nullable',
                'date',
                'before:end_date',
                'after_or_equal:start_date'
            ]
        ]);

        Tournament::create($validated);

        return redirect()
            ->route('admin.tournaments.index')
            ->with('success', 'Tournament created successfully.');
    }

    /**
     * Display the specified tournament.
     */
    public function show(Tournament $tournament)
    {
        $this->checkPermission('manage_tournaments');
        $tournament->load(['sport', 'teams.players', 'settings']);
        return view('admin.tournaments.show', compact('tournament'));
    }

    /**
     * Show the form for editing the specified tournament.
     */
    public function edit(Tournament $tournament)
    {
        $this->checkPermission('manage_tournaments');
        $sports = Sport::orderBy('name')->get();
        return view('admin.tournaments.edit', compact('tournament', 'sports'));
    }

    /**
     * Update the specified tournament in storage.
     */
    public function update(Request $request, Tournament $tournament)
    {
        $this->checkPermission('manage_tournaments');
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255'
            ],
            'sport_id' => [
                'required',
                'exists:sports,id',
                'integer'
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'start_date' => [
                'required',
                'date',
                'after_or_equal:today'
            ],
            'end_date' => [
                'required',
                'date',
                'after:start_date'
            ],
            'status' => [
                'required',
                'string',
                'in:draft,active,completed,cancelled'
            ],
            'max_teams' => [
                'nullable',
                'integer',
                'min:2'
            ],
            'registration_deadline' => [
                'nullable',
                'date',
                'before:end_date',
                'after_or_equal:start_date'
            ]
        ]);

        $tournament->update($validated);

        return redirect()
            ->route('admin.tournaments.index')
            ->with('success', 'Tournament updated successfully.');
    }

    /**
     * Remove the specified tournament from storage.
     */
    public function destroy(Tournament $tournament)
    {
        $this->checkPermission('manage_tournaments');
        $tournament->delete();

        return redirect()
            ->route('admin.tournaments.index')
            ->with('success', 'Tournament deleted successfully.');
    }

    /**
     * Generate round-robin schedule for tournament.
     */
    public function scheduleMatches(Tournament $tournament, MatchScheduler $matchScheduler)
    {
        $this->checkPermission('manage_tournaments');
        try {
            // Eager load relationships to avoid N+1 queries
            $tournament->load(['teams', 'settings', 'matches']);

            // Check if tournament has teams
            if ($tournament->teams->count() < 2) {
                return redirect()
                    ->route('admin.tournaments.show', $tournament->id)
                    ->with('error', 'At least 2 teams are required to generate a schedule.');
            }

            // Check if tournament has settings
            if (!$tournament->settings) {
                return redirect()
                    ->route('admin.tournaments.show', $tournament->id)
                    ->with('error', 'Tournament settings are required to generate a schedule.');
            }

            // Check if matches already exist
            if ($tournament->matches->count() > 0) {
                return redirect()
                    ->route('admin.tournaments.show', $tournament->id)
                    ->with('error', 'Matches already exist for this tournament. Delete existing matches first.');
            }

            // Generate round-robin schedule
            $matches = $matchScheduler->generateRoundRobinSchedule($tournament);

            return redirect()
                ->route('admin.tournaments.show', $tournament->id)
                ->with('success', "Successfully generated {$matches->count()} matches for {$tournament->name}.");

        } catch (\Exception $e) {
            return redirect()
                ->route('admin.tournaments.show', $tournament->id)
                ->with('error', 'Failed to generate schedule: ' . $e->getMessage());
        }
    }

    /**
     * Manually recalculate standings for a tournament (for testing/debugging).
     */
    public function recalculateStandings(Tournament $tournament, StandingsCalculator $standingsCalculator)
    {
        $this->checkPermission('manage_tournaments');
        try {
            // Eager load teams to avoid N+1 queries
            $tournament->load('teams');

            // Check if tournament has teams
            if ($tournament->teams->count() < 1) {
                return redirect()
                    ->route('admin.tournaments.show', $tournament->id)
                    ->with('error', 'Tournament must have at least one team to calculate standings.');
            }

            // Recalculate standings
            $standingsCalculator->calculateTournamentStandings($tournament);

            return redirect()
                ->route('admin.tournaments.show', $tournament->id)
                ->with('success', 'Standings recalculated successfully for ' . $tournament->name . '.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.tournaments.show', $tournament->id)
                ->with('error', 'Failed to recalculate standings: ' . $e->getMessage());
        }
    }
}
