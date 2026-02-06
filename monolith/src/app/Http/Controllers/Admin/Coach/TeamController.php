<?php

namespace App\Http\Controllers\Admin\Coach;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TeamController extends Controller
{
    /**
     * Display the coach's team.
     */
    public function index()
    {
        $user = Auth::user();
        $teams = $user->teams()->with(['tournament', 'players', 'coaches'])->get();

        return view('admin.coach.teams.index', compact('teams'));
    }

    /**
     * Display the specified team.
     */
    public function show(Team $team)
    {
        $user = Auth::user();
        // Eager load user teams to avoid N+1
        if (!$user->teams()->where('team_id', $team->id)->exists()) {
            abort(403, 'Unauthorized - You are not a coach of this team');
        }

        // Eager load all relationships to avoid N+1 queries
        $team->load(['tournament', 'players', 'coaches']);

        return view('admin.coach.teams.show', compact('team'));
    }

    /**
     * Show the form for editing the specified team.
     */
    public function edit(Team $team)
    {
        $user = Auth::user();
        // Eager load user teams to avoid N+1
        if (!$user->teams()->where('team_id', $team->id)->exists()) {
            abort(403, 'Unauthorized - You are not a coach of this team');
        }

        // Eager load all relationships to avoid N+1 queries
        $team->load(['tournament', 'players', 'coaches']);

        return view('admin.coach.teams.edit', compact('team'));
    }

    /**
     * Update the specified team in storage.
     */
    public function update(Request $request, Team $team)
    {
        $user = Auth::user();
        // Eager load user teams to avoid N+1
        if (!$user->teams()->where('team_id', $team->id)->exists()) {
            abort(403, 'Unauthorized - You are not a coach of this team');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'coach_name' => ['nullable', 'string', 'max:255'],
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

        return redirect()
            ->route('admin.coach.teams.show', $team->id)
            ->with('success', 'Team updated successfully.');
    }
}
