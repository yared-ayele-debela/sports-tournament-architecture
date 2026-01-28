<?php

namespace App\Http\Controllers\Admin\Coach;

use App\Http\Controllers\Controller;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PlayerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Team $team)
    {
        $user = Auth::user();
        
        // Check if the authenticated user is a coach of this team
        if (!$user->teams()->exists()) {
            abort(403, 'Unauthorized - You are not a coach of this team');
        }
        
        $players = $team->players()->orderBy('jersey_number')->get();
        
        return view('admin.coach.players.index', compact('team', 'players'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Team $team)
    {
        $user = Auth::user();
        // dd($user);
        if (!$user->teams()->exists()) {
            abort(403, 'Unauthorized - You are not a coach of this team');
        }
        
        return view('admin.coach.players.create', compact('team'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Team $team)
    {
        $user = Auth::user();
        if (!$user->teams()->exists()) {
            abort(403, 'Unauthorized - You are not a coach of this team');
        }
        
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'in:Goalkeeper,Defender,Midfielder,Forward'],
            'jersey_number' => [
                'required', 
                'integer', 
                'min:1', 
                'max:99',
                Rule::unique('players')->where(function ($query) use ($team) {
                    return $query->where('team_id', $team->id);
                })
            ]
        ]);

        Player::create(array_merge($validated, ['team_id' => $team->id]));

        return redirect()
            ->route('admin.coach.players.index', $team->id)
            ->with('success', 'Player created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Team $team, Player $player)
    {
        $user = Auth::user();
        if (!$user->teams()->exists()) {
            abort(403, 'Unauthorized - You are not a coach of this team');
        }
        
        return view('admin.coach.players.show', compact('team', 'player'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Team $team, Player $player)
    {
        $user = Auth::user();
        if (!$user->teams()->exists()) {
            abort(403, 'Unauthorized - You are not a coach of this team');
        }
        
        return view('admin.coach.players.edit', compact('team', 'player'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Team $team, Player $player)
    {
        $user = Auth::user();
        if (!$user->teams()->exists()) {
            abort(403, 'Unauthorized - You are not a coach of this team');
        }
        
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'in:Goalkeeper,Defender,Midfielder,Forward'],
            'jersey_number' => [
                'required', 
                'integer', 
                'min:1', 
                'max:99',
                Rule::unique('players')->where(function ($query) use ($team) {
                    return $query->where('team_id', $team->id);
                })->ignore($player->id)
            ]
        ]);

        $player->update($validated);

        return redirect()
            ->route('admin.coach.players.index', $team->id)
            ->with('success', 'Player updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Team $team, Player $player)
    {
        $user = Auth::user();
        if (!$user->teams()->exists()) {
            abort(403, 'Unauthorized - You are not a coach of this team');
        }
        
        $player->delete();

        return redirect()
            ->route('admin.coach.players.index', $team->id)
            ->with('success', 'Player deleted successfully.');
    }
}
