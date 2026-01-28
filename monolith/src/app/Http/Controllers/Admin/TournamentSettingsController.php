<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TournamentSettings;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TournamentSettingsController extends Controller
{
    /**
     * Display tournament settings.
     */
    public function index()
    {
        $tournaments = Tournament::orderBy('name')->get();
        $settings = TournamentSettings::with('tournament')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('admin.tournament-settings.index', compact('tournaments', 'settings'));
    }

    /**
     * Show form for creating tournament settings.
     */
    public function create()
    {
        $tournaments = Tournament::orderBy('name')->get();
        return view('admin.tournament-settings.create', compact('tournaments'));
    }

    /**
     * Store tournament settings in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tournament_id' => [
                'required',
                'exists:tournaments,id',
                'integer',
                'unique:tournament_settings,tournament_id'
            ],
            'match_duration' => [
                'required',
                'integer',
                'min:1',
                'max:480'
            ],
            'win_rest_time' => [
                'required',
                'integer',
                'min:0',
                'max:60'
            ],
            'daily_start_time' => [
                'required',
                'date_format:H:i',
                'before:daily_end_time'
            ],
            'daily_end_time' => [
                'required',
                'date_format:H:i',
                'after:daily_start_time'
            ]
        ]);

        TournamentSettings::create($validated);

        return redirect()
            ->route('admin.tournament-settings.index')
            ->with('success', 'Tournament settings created successfully.');
    }

    /**
     * Display specified tournament settings.
     */
    public function show(TournamentSettings $tournamentSetting)
    {
        $tournamentSetting = $tournamentSetting->load('tournament');
        return view('admin.tournament-settings.show', compact('tournamentSetting'));
    }

    /**
     * Show form for editing tournament settings.
     */
    public function edit(TournamentSettings $tournamentSetting)
    {
        $tournaments = Tournament::orderBy('name')->get();
        return view('admin.tournament-settings.edit', compact('tournamentSetting', 'tournaments'));
    }

    /**
     * Update tournament settings in storage.
     */
    public function update(Request $request, TournamentSettings $tournamentSetting)
    {
        $validated = $request->validate([
            'tournament_id' => [
                'required',
                'exists:tournaments,id',
                'integer',
                Rule::unique('tournament_settings', 'tournament_id')->ignore($tournamentSetting->id)
            ],
            'match_duration' => [
                'required',
                'integer',
                'min:1',
                'max:480'
            ],
            'win_rest_time' => [
                'required',
                'integer',
                'min:0',
                'max:60'
            ],
            'daily_start_time' => [
                'required',
                'date_format:H:i',
                'before:daily_end_time'
            ],
            'daily_end_time' => [
                'required',
                'date_format:H:i',
                'after:daily_start_time'
            ]
        ]);

        $tournamentSetting->update($validated);

        return redirect()
            ->route('admin.tournament-settings.index')
            ->with('success', 'Tournament settings updated successfully.');
    }

    /**
     * Remove tournament settings from storage.
     */
    public function destroy(TournamentSettings $tournamentSetting)
    {
        $tournamentSetting->delete();

        return redirect()
            ->route('admin.tournament-settings.index')
            ->with('success', 'Tournament settings deleted successfully.');
    }
}
