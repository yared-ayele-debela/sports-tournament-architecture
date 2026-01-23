<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchGame;
use App\Models\MatchReport;
use App\Services\EventPublisher;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MatchReportController extends Controller
{
    protected EventPublisher $eventPublisher;

    public function __construct(EventPublisher $eventPublisher)
    {
        $this->eventPublisher = $eventPublisher;
    }

    public function show(string $matchId): JsonResponse
    {
        $report = MatchReport::where('match_id', $matchId)
            ->with('match')
            ->firstOrFail();

        return ApiResponse::success($report);
    }

    public function store(Request $request, string $matchId): JsonResponse
    {
        $match = MatchGame::findOrFail($matchId);

        Log::alert(['in match',$match]);
        $validated = $request->validate([
            'summary' => 'required|string|max:2000',
            'referee' => 'required|string|max:255',
    'attendance' => 'required|integer|min:0',
        ]);

        Log::alert("before store");
        // Create or update report
        $report = MatchReport::updateOrCreate(
            ['match_id' => $matchId],
            $validated
        );
        Log::alert("after store");

        // Mark match as completed
        $match->status = 'completed';
        $match->save();

        // Publish Redis event using EventPublisher
        $this->eventPublisher->publishMatchCompleted(
            $match->toArray(),
            $report->toArray()
        );

        return ApiResponse::created($report->load('match'));
    }
}
