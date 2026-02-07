<?php

namespace App\Http\Controllers\Admin\Referee;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use App\Models\MatchReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MatchReportController extends Controller
{
    /**
     * Show the form for creating a new match report
     */
    public function create(MatchModel $match)
    {
        $this->checkPermission('submit_reports');

        // Authorization: Check if the match belongs to the authenticated referee
        if ($match->referee_id !== Auth::id()) {
            abort(403, 'You are not authorized to create a report for this match.');
        }

        // Check if report already exists
        if ($match->matchReport) {
            return redirect()
                ->route('admin.referee.reports.edit', $match)
                ->with('info', 'A report already exists for this match. You can edit it instead.');
        }

        return view('admin.referee.reports.create', compact('match'));
    }

    /**
     * Store a newly created match report
     */
    public function store(Request $request, MatchModel $match)
    {
        $this->checkPermission('submit_reports');

        // Authorization: Check if the match belongs to the authenticated referee
        if ($match->referee_id !== Auth::id()) {
            abort(403, 'You are not authorized to create a report for this match.');
        }

        // Check if report already exists
        if ($match->matchReport) {
            return redirect()
                ->route('admin.referee.reports.edit', $match)
                ->with('error', 'A report already exists for this match.');
        }

        $validated = $request->validate([
            'summary' => 'required|string|min:10|max:5000',
            'referee' => 'nullable|string|max:255',
            'attendance' => 'nullable|string|max:255',
        ]);

        // Set referee name if not provided
        if (empty($validated['referee'])) {
            $validated['referee'] = Auth::user()->name;
        }

        MatchReport::create([
            'match_id' => $match->id,
            'summary' => $validated['summary'],
            'referee' => $validated['referee'],
            'attendance' => $validated['attendance'],
        ]);

        return redirect()
            ->route('admin.referee.matches.show', $match)
            ->with('success', 'Match report created successfully.');
    }

    /**
     * Show the form for editing the specified match report
     */
    public function edit(MatchModel $match)
    {
        $this->checkPermission('submit_reports');

        // Authorization: Check if the match belongs to the authenticated referee
        if ($match->referee_id !== Auth::id()) {
            abort(403, 'You are not authorized to edit this report.');
        }

        $report = $match->matchReport;

        if (!$report) {
            return redirect()
                ->route('admin.referee.reports.create', $match)
                ->with('info', 'No report exists for this match. Create one first.');
        }

        return view('admin.referee.reports.edit', compact('match', 'report'));
    }

    /**
     * Update the specified match report
     */
    public function update(Request $request, MatchModel $match)
    {
        $this->checkPermission('submit_reports');

        // Authorization: Check if the match belongs to the authenticated referee
        if ($match->referee_id !== Auth::id()) {
            abort(403, 'You are not authorized to update this report.');
        }

        $report = $match->matchReport;

        if (!$report) {
            return redirect()
                ->route('admin.referee.reports.create', $match)
                ->with('error', 'No report exists for this match.');
        }

        $validated = $request->validate([
            'summary' => 'required|string|min:10|max:5000',
            'referee' => 'nullable|string|max:255',
            'attendance' => 'nullable|string|max:255',
        ]);

        $report->update($validated);

        return redirect()
            ->route('admin.referee.matches.show', $match)
            ->with('success', 'Match report updated successfully.');
    }

    /**
     * Display the specified match report
     */
    public function show(MatchModel $match)
    {
        $this->checkPermission('submit_reports');

        // Authorization: Check if the match belongs to the authenticated referee
        if ($match->referee_id !== Auth::id()) {
            abort(403, 'You are not authorized to view this report.');
        }

        $report = $match->matchReport;

        if (!$report) {
            return redirect()
                ->route('admin.referee.matches.show', $match)
                ->with('info', 'No report exists for this match yet.');
        }

        return view('admin.referee.reports.show', compact('match', 'report'));
    }
}
