<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchGame;
use App\Models\MatchReport;
use App\Services\Events\EventPublisher;
use App\Services\Events\EventPayloadBuilder;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
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
        $match->save();

        // Publish match completed event (CRITICAL for standings)
        $this->publishMatchCompletedEvent($match, $report, ['id' => Auth::id(), 'name' => 'Admin']);

        return ApiResponse::created($report->load('match'));
    }

    /**
     * Publish match completed event (CRITICAL for standings)
     *
     * @param MatchGame $match
     * @param MatchReport $report
     * @param array $user
     * @return void
     */
    protected function publishMatchCompletedEvent(MatchGame $match, MatchReport $report, array $user): void
    {
        try {
            $payload = EventPayloadBuilder::matchCompleted($match, $report, $user);
            $this->eventPublisher->publish('sports.match.completed', $payload);
            
            Log::info('Match completed event published', [
                'match_id' => $match->id,
                'home_score' => $report->home_score,
                'away_score' => $report->away_score,
                'tournament_id' => $match->tournament_id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to publish match completed event', [
                'match_id' => $match->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
