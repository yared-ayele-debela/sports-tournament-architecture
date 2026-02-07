<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use App\Models\Tournament;
use App\Models\Team;
use App\Models\Venue;
use App\Models\User;
use App\Services\MatchService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MatchController extends Controller
{
    protected MatchService $matchService;

    public function __construct(MatchService $matchService)
    {
        $this->matchService = $matchService;
    }
    /**
     * Display a listing of matches.
     */
    public function index()
    {
        $this->checkPermission('manage_matches');
        $matches = MatchModel::with(['tournament', 'homeTeam', 'awayTeam', 'venue', 'referee', 'matchReport'])
            ->orderBy('match_date', 'desc')
            ->paginate(10);
        return view('admin.matches.index', compact('matches'));
    }

    /**
     * Show the form for creating a new match.
     */
    public function create()
    {
        $this->checkPermission('manage_matches');
        $tournaments = Tournament::orderBy('name')->get();
        $teams = Team::orderBy('name')->get();
        $venues = Venue::orderBy('name')->get();
        $referees = User::whereHas('roles', function($query) {
            $query->where('name', 'referee');
        })->orderBy('name')->get();
        return view('admin.matches.create', compact('tournaments', 'teams', 'venues', 'referees'));
    }

    /**
     * Store a newly created match in storage.
     */
    public function store(Request $request)
    {
        $this->checkPermission('manage_matches');
        $validated = $request->validate([
            'tournament_id' => ['required', 'exists:tournaments,id', 'integer'],
            'venue_id' => ['nullable', 'exists:venues,id', 'integer'],
            'home_team_id' => ['required', 'exists:teams,id', 'integer'],
            'away_team_id' => ['required', 'exists:teams,id', 'integer', 'different:home_team_id'],
            'referee_id' => ['nullable', 'exists:users,id', 'integer'],
            'match_date' => ['required', 'date', 'after:now'],
            'round_number' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', 'in:scheduled,in_progress,completed,cancelled'],
            'home_score' => ['nullable', 'integer', 'min:0'],
            'away_score' => ['nullable', 'integer', 'min:0'],
            'current_minute' => ['nullable', 'integer', 'min:0', 'max:120']
        ]);

        try {
            // Use service to create match (includes validation)
            $this->matchService->createMatch($validated);

            return redirect()
                ->route('admin.matches.index')
                ->with('success', 'Match created successfully.');
        } catch (\App\Exceptions\BusinessLogicException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getUserMessage())
                ->withInput();
        } catch (\App\Exceptions\ResourceNotFoundException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage())
                ->withInput();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    /**
     * Display the specified match.
     */
    public function show(MatchModel $match)
    {
        $this->checkPermission('manage_matches');
        $match->load(['tournament', 'homeTeam', 'awayTeam', 'venue', 'referee', 'matchReport']);
        return view('admin.matches.show', compact('match'));
    }

    /**
     * Show the form for editing the specified match.
     */
    public function edit(MatchModel $match)
    {
        $this->checkPermission('manage_matches');
        $tournaments = Tournament::orderBy('name')->get();
        $teams = Team::orderBy('name')->get();
        $venues = Venue::orderBy('name')->get();
        $referees = User::whereHas('roles', function($query) {
            $query->where('name', 'referee');
        })->orderBy('name')->get();
        return view('admin.matches.edit', compact('match', 'tournaments', 'teams', 'venues', 'referees'));
    }

    /**
     * Update the specified match in storage.
     */
    public function update(Request $request, MatchModel $match)
    {
        $this->checkPermission('manage_matches');
        $validated = $request->validate([
            'tournament_id' => ['required', 'exists:tournaments,id', 'integer'],
            'venue_id' => ['nullable', 'exists:venues,id', 'integer'],
            'home_team_id' => ['required', 'exists:teams,id', 'integer'],
            'away_team_id' => ['required', 'exists:teams,id', 'integer', 'different:home_team_id'],
            'referee_id' => ['nullable', 'exists:users,id', 'integer'],
            'match_date' => ['required', 'date', 'after:now'],
            'round_number' => ['required', 'integer', 'min:1'],
            'status' => ['required', 'string', 'in:scheduled,in_progress,completed,cancelled'],
            'home_score' => ['nullable', 'integer', 'min:0'],
            'away_score' => ['nullable', 'integer', 'min:0'],
            'current_minute' => ['nullable', 'integer', 'min:0', 'max:120']
        ]);

        try {
            // Use service to update match (includes validation)
            $this->matchService->updateMatch($match, $validated);

            return redirect()
                ->route('admin.matches.index')
                ->with('success', 'Match updated successfully.');
        } catch (\App\Exceptions\BusinessLogicException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getUserMessage())
                ->withInput();
        } catch (\App\Exceptions\ResourceNotFoundException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage())
                ->withInput();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    /**
     * Remove the specified match from storage.
     */
    public function destroy(MatchModel $match)
    {
        $this->checkPermission('manage_matches');
        $match->delete();

        return redirect()
            ->route('admin.matches.index')
            ->with('success', 'Match deleted successfully.');
    }
}
