<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchGame;
use App\Models\MatchReport;
use App\Services\Queue\QueuePublisher;
use App\Services\Events\EventPayloadBuilder;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MatchReportController extends Controller
{
    protected QueuePublisher $queuePublisher;

    public function __construct(QueuePublisher $queuePublisher)
    {
        $this->queuePublisher = $queuePublisher;
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

        $validated = $request->validate([
            'summary' => 'required|string|max:2000',
            'referee' => 'required|string|max:255',
            'attendance' => 'required|integer|min:0',
            'home_score' => 'required|integer|min:0',
            'away_score' => 'required|integer|min:0',
            'duration_minutes' => 'sometimes|integer|min:1|max:180',
        ]);

        // Create or update report
        $report = MatchReport::updateOrCreate(
            ['match_id' => $matchId],
            array_merge($validated, [
                'completed_at' => now(),
                'duration_minutes' => $validated['duration_minutes'] ?? 90,
            ])
        );

        // Mark match as completed
        $match->status = 'completed';
        $match->home_score = $validated['home_score'];
        $match->away_score = $validated['away_score'];
        $match->save(); // Save first to ensure scores are in the database

        // Dispatch match completed event to queue (high priority - CRITICAL for standings)
        $user = Auth::user();
        $this->dispatchMatchCompletedQueueEvent($match, $report, [
            'id' => Auth::id() ?? null,
            'name' => $user?->name ?? 'System'
        ]);

        return ApiResponse::created($report->load('match'));
    }

    /**
     * Dispatch match completed event to queue (high priority - CRITICAL for standings)
     *
     * @param MatchGame $match
     * @param MatchReport $report
     * @param array $user
     * @return void
     */
    protected function dispatchMatchCompletedQueueEvent(MatchGame $match, MatchReport $report, array $user): void
    {
        try {
            // Build payload with all required fields for standings calculation
            $payload = [
                'match_id' => $match->id,
                'tournament_id' => $match->tournament_id,
                'home_team_id' => $match->home_team_id,
                'away_team_id' => $match->away_team_id,
                'home_score' => (int) ($match->home_score ?? 0),
                'away_score' => (int) ($match->away_score ?? 0),
                'completed_at' => $report->completed_at?->toIso8601String() ?? now()->toIso8601String(),
                'match_date' => $match->match_date?->toIso8601String(),
                'result' => $this->determineResult((int) ($match->home_score ?? 0), (int) ($match->away_score ?? 0)),
            ];

            // Dispatch to high priority queue (CRITICAL - triggers standings calculation)
            $this->queuePublisher->dispatchHigh('events', $payload, 'match.completed');

            Log::info('Match completed event dispatched to queue', [
                'match_id' => $match->id,
                'home_score' => $match->home_score,
                'away_score' => $match->away_score,
                'tournament_id' => $match->tournament_id,
                'result' => $payload['result']
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch match completed queue event', [
                'match_id' => $match->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Determine match result
     *
     * @param int $homeScore
     * @param int $awayScore
     * @return string
     */
    protected function determineResult(int $homeScore, int $awayScore): string
    {
        if ($homeScore > $awayScore) {
            return 'home_win';
        } elseif ($homeScore < $awayScore) {
            return 'away_win';
        } else {
            return 'draw';
        }
    }
}
