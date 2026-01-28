<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TeamController extends Controller
{
    /**
     * Display a listing of teams.
     */
    public function index()
    {
        $teams = Team::with(['tournament', 'coaches'])->orderBy('name')->paginate(10);
        return view('admin.teams.index', compact('teams'));
    }

    /**
     * Show the form for creating a new team.
     */
    public function create()
    {
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

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('team-logos', 'public');
            $validated['logo'] = $logoPath;
        }

        $team = Team::create($validated);

        // Attach coach if provided
        if ($request->filled('coach_id')) {
            $team->coaches()->attach($request->coach_id);
        }

        return redirect()
            ->route('admin.teams.index')
            ->with('success', 'Team created successfully.');
    }

    /**
     * Display the specified team.
     */
    public function show(Team $team)
    {
        $team->load('tournament');
        return view('admin.teams.show', compact('team'));
    }

    /**
     * Show the form for editing the specified team.
     */
    public function edit(Team $team)
    {
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

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($team->logo) {
                Storage::disk('public')->delete($team->logo);
            }
            
            $logoPath = $request->file('logo')->store('team-logos', 'public');
            $validated['logo'] = $logoPath;
        }

        $team->update($validated);

        // Sync coach relationship
        if ($request->filled('coach_id')) {
            $team->coaches()->sync([$request->coach_id]);
        } else {
            $team->coaches()->detach();
        }

        return redirect()
            ->route('admin.teams.index')
            ->with('success', 'Team updated successfully.');
    }

    /**
     * Remove the specified team from storage.
     */
    public function destroy(Team $team)
    {
        // Delete logo if exists
        if ($team->logo) {
            Storage::disk('public')->delete($team->logo);
        }
        
        $team->delete();

        return redirect()
            ->route('admin.teams.index')
            ->with('success', 'Team deleted successfully.');
    }
}
