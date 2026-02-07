<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use App\Services\TeamService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    protected TeamService $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }
    /**
     * Display a listing of teams.
     */
    public function index()
    {
        $this->checkPermission('manage_teams');
        $teams = Team::with(['tournament', 'coaches'])->orderBy('name')->paginate(10);
        return view('admin.teams.index', compact('teams'));
    }

    /**
     * Show the form for creating a new team.
     */
    public function create()
    {
        $this->checkPermission('manage_teams');
        $tournaments = Tournament::orderBy('name')->get();
        $coaches = User::whereHas('roles', function($query) {
            $query->where('name', 'coach');
        })->orderBy('name')->get();

        return view('admin.teams.create', compact('tournaments', 'coaches'));
    }

    /**
     * Store a newly created team in storage.
     */
    public function store(Request $request)
    {
        $this->checkPermission('manage_teams');
        $validated = $request->validate([
            'tournament_id' => ['required', 'exists:tournaments,id', 'integer'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('teams')->where(function ($query) use ($request) {
                    return $query->where('tournament_id', $request->tournament_id);
                })
            ],
            'coach_id' => ['nullable', 'exists:users,id', 'integer'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048']
        ]);

        try {
            // Use service to create team
            $this->teamService->createTeam(
                $validated,
                $request->file('logo'),
                $request->input('coach_id')
            );

            return redirect()
                ->route('admin.teams.index')
                ->with('success', 'Team created successfully.');
        } catch (\App\Exceptions\BusinessLogicException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getUserMessage())
                ->withErrors(['coach_id' => $e->getUserMessage()]);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'An unexpected error occurred. Please try again.');
        }
    }

    /**
     * Display the specified team.
     */
    public function show(Team $team)
    {
        $this->checkPermission('manage_teams');
        // Eager load all relationships to avoid N+1 queries
        $team->load(['tournament', 'players', 'coaches']);
        return view('admin.teams.show', compact('team'));
    }

    /**
     * Show the form for editing the specified team.
     */
    public function edit(Team $team)
    {
        $this->checkPermission('manage_teams');
        $tournaments = Tournament::orderBy('name')->get();
        $coaches = User::whereHas('roles', function($query) {
            $query->where('name', 'coach');
        })->orderBy('name')->get();

        $team->load('coaches');

        return view('admin.teams.edit', compact('team', 'tournaments', 'coaches'));
    }

    /**
     * Update the specified team in storage.
     */
    public function update(Request $request, Team $team)
    {
        $this->checkPermission('manage_teams');
        $validated = $request->validate([
            'tournament_id' => ['required', 'exists:tournaments,id', 'integer'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('teams')->where(function ($query) use ($request) {
                    return $query->where('tournament_id', $request->tournament_id);
                })->ignore($team->id)
            ],
            'coach_id' => ['nullable', 'exists:users,id', 'integer'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048']
        ]);

        try {
            // Use service to update team
            $this->teamService->updateTeam(
                $team,
                $validated,
                $request->file('logo'),
                $request->input('coach_id')
            );

            return redirect()
                ->route('admin.teams.index')
                ->with('success', 'Team updated successfully.');
        } catch (\App\Exceptions\BusinessLogicException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getUserMessage())
                ->withErrors(['coach_id' => $e->getUserMessage()]);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'An unexpected error occurred. Please try again.');
        }
    }

    /**
     * Remove the specified team from storage.
     */
    public function destroy(Team $team)
    {
        $this->checkPermission('manage_teams');

        try {
            // Use service to delete team
            $this->teamService->deleteTeam($team);

            return redirect()
                ->route('admin.teams.index')
                ->with('success', 'Team deleted successfully.');
        } catch (\App\Exceptions\BusinessLogicException $e) {
            return redirect()
                ->back()
                ->with('error', $e->getUserMessage());
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'An unexpected error occurred. Please try again.');
        }
    }
}
