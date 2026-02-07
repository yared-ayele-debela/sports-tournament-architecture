<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Venue;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    /**
     * Display a listing of venues.
     */
    public function index()
    {
        $this->checkPermission('manage_venues');
        $venues = Venue::orderBy('name')->paginate(10);
        return view('admin.venues.index', compact('venues'));
    }

    /**
     * Show the form for creating a new venue.
     */
    public function create()
    {
        $this->checkPermission('manage_venues');
        return view('admin.venues.create');
    }

    /**
     * Store a newly created venue in storage.
     */
    public function store(Request $request)
    {
        $this->checkPermission('manage_venues');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:999999']
        ]);

        Venue::create($validated);

        return redirect()
            ->route('admin.venues.index')
            ->with('success', 'Venue created successfully.');
    }

    /**
     * Display the specified venue.
     */
    public function show(Venue $venue)
    {
        $this->checkPermission('manage_venues');
        return view('admin.venues.show', compact('venue'));
    }

    /**
     * Show the form for editing the specified venue.
     */
    public function edit(Venue $venue)
    {
        $this->checkPermission('manage_venues');
        return view('admin.venues.edit', compact('venue'));
    }

    /**
     * Update the specified venue in storage.
     */
    public function update(Request $request, Venue $venue)
    {
        $this->checkPermission('manage_venues');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:999999']
        ]);

        $venue->update($validated);

        return redirect()
            ->route('admin.venues.index')
            ->with('success', 'Venue updated successfully.');
    }

    /**
     * Remove the specified venue from storage.
     */
    public function destroy(Venue $venue)
    {
        $this->checkPermission('manage_venues');
        $venue->delete();

        return redirect()
            ->route('admin.venues.index')
            ->with('success', 'Venue deleted successfully.');
    }
}
