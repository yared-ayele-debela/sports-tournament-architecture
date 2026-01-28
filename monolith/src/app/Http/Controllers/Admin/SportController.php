<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sport;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SportController extends Controller
{
    /**
     * Display a listing of sports.
     */
    public function index()
    {
        $sports = Sport::orderBy('name')->paginate(10);
        return view('admin.sports.index', compact('sports'));
    }

    /**
     * Show the form for creating a new sport.
     */
    public function create()
    {
        return view('admin.sports.create');
    }

    /**
     * Store a newly created sport in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:sports,name'
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'is_active' => [
                'boolean'
            ]
        ]);

        Sport::create($validated);

        return redirect()
            ->route('admin.sports.index')
            ->with('success', 'Sport created successfully.');
    }

    /**
     * Display the specified sport.
     */
    public function show(Sport $sport)
    {
        return view('admin.sports.show', compact('sport'));
    }

    /**
     * Show the form for editing the specified sport.
     */
    public function edit(Sport $sport)
    {
        return view('admin.sports.edit', compact('sport'));
    }

    /**
     * Update the specified sport in storage.
     */
    public function update(Request $request, Sport $sport)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('sports', 'name')->ignore($sport->id)
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000'
            ],
            'is_active' => [
                'boolean'
            ]
        ]);

        $sport->update($validated);

        return redirect()
            ->route('admin.sports.index')
            ->with('success', 'Sport updated successfully.');
    }

    /**
     * Remove the specified sport from storage.
     */
    public function destroy(Sport $sport)
    {
        $sport->delete();

        return redirect()
            ->route('admin.sports.index')
            ->with('success', 'Sport deleted successfully.');
    }
}
