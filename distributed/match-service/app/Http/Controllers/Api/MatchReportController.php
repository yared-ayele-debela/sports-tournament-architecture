<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchGame;
use App\Models\MatchReport;
use App\Services\EventPublisher;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MatchReportController extends Controller
{
    protected EventPublisher $eventPublisher;

    public function __construct(EventPublisher $eventPublisher)
    {
        $this->middleware('auth:api');
        $this->eventPublisher = $eventPublisher;
    }

    public function show(string $matchId): JsonResponse
    {
        $report = MatchReport::where('match_id', $matchId)
            ->with('match')
            ->firstOrFail();

        return response()->json($report);
    }

    public function store(Request $request, string $matchId): JsonResponse
    {
        $match = MatchGame::findOrFail($matchId);

        $validated = $request->validate([
            'summary' => 'required|string|max:2000',
            'referee' => 'required|string|max:255',
            'attendance' => 'required|string|max:100',
        ]);

        // Create or update report
        $report = MatchReport::updateOrCreate(
            ['match_id' => $matchId],
            $validated
        );

        // Mark match as completed
        $match->status = 'completed';
        $match->save();

        // Publish Redis event using EventPublisher
        $this->eventPublisher->publishMatchCompleted(
            $match->toArray(),
            $report->toArray()
        );

        return response()->json($report->load('match'), 201);
    }
}
