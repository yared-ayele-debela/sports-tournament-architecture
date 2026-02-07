<?php

namespace App\Services;

use App\Models\MatchModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MatchTimerService
{
    /**
     * Start the match timer
     */
    public function start(MatchModel $match): void
    {
        $match->update([
            'status' => 'in_progress',
            'current_minute' => 0,
            'started_at' => now(),
            'paused_at' => null,
            'total_paused_seconds' => 0,
            'last_minute_update' => now(),
        ]);

        Log::info("Match {$match->id} timer started at " . now());
    }

    /**
     * Pause the match timer
     */
    public function pause(MatchModel $match): void
    {
        if ($match->status !== 'in_progress') {
            return;
        }

        // Update minute before pausing to get accurate current minute
        if ($match->started_at) {
            $this->updateMinute($match);
        }

        // Just mark as paused - we'll calculate pause duration when resuming
        $match->update([
            'status' => 'paused',
            'paused_at' => now(),
        ]);

        Log::info("Match {$match->id} timer paused at " . now() . " - Minute: {$match->current_minute}");
    }

    /**
     * Resume the match timer
     */
    public function resume(MatchModel $match): void
    {
        if ($match->status !== 'paused') {
            return;
        }

        // Calculate how long it was paused and add to total paused time
        if ($match->paused_at) {
            $pauseDuration = now()->diffInSeconds($match->paused_at);
            $match->update([
                'total_paused_seconds' => ($match->total_paused_seconds ?? 0) + $pauseDuration,
                'paused_at' => null,
                'last_minute_update' => now(),
            ]);
        } else {
            // If paused_at is null, just reset it
            $match->update([
                'paused_at' => null,
                'last_minute_update' => now(),
            ]);
        }

        $match->update([
            'status' => 'in_progress',
        ]);

        Log::info("Match {$match->id} timer resumed at " . now() . " - Total paused: {$match->total_paused_seconds}s");
    }

    /**
     * End the match timer
     */
    public function end(MatchModel $match): void
    {
        // Calculate final minute before ending
        $this->updateMinute($match);

        $match->update([
            'status' => 'completed',
            'paused_at' => null,
        ]);

        Log::info("Match {$match->id} timer ended at " . now() . " - Final minute: {$match->current_minute}");
    }

    /**
     * Calculate and update current minute based on elapsed time
     */
    public function updateMinute(MatchModel $match): int
    {
        if ($match->status !== 'in_progress' || !$match->started_at) {
            return max(0, $match->current_minute ?? 0);
        }

        // Calculate elapsed seconds since match started
        $elapsedSeconds = now()->diffInSeconds($match->started_at);

        // Get total paused time (including current pause if any)
        $totalPausedSeconds = $match->total_paused_seconds ?? 0;

        // If currently paused, add the current pause duration
        if ($match->paused_at) {
            $currentPauseDuration = now()->diffInSeconds($match->paused_at);
            $totalPausedSeconds += $currentPauseDuration;
        }

        // Subtract total paused time from elapsed time
        $activeSeconds = $elapsedSeconds - $totalPausedSeconds;

        // Ensure we don't get negative values
        $activeSeconds = max(0, $activeSeconds);

        // Convert to minutes (round down)
        $currentMinute = (int) floor($activeSeconds / 60);

        // Get match duration from tournament settings (default 90 minutes)
        // Load tournament relationship if not loaded
        if (!$match->relationLoaded('tournament')) {
            $match->load('tournament.settings');
        }

        $matchDuration = 90; // Default
        if ($match->tournament && $match->tournament->settings) {
            $matchDuration = $match->tournament->settings->match_duration ?? 90;
        }

        // Cap at match duration
        if ($currentMinute > $matchDuration) {
            $currentMinute = $matchDuration;
        }

        // Ensure non-negative
        $currentMinute = max(0, $currentMinute);

        // Only update if minute changed
        if ($currentMinute !== $match->current_minute) {
            $match->update([
                'current_minute' => $currentMinute,
                'last_minute_update' => now(),
            ]);

            // Auto-end match if duration reached
            if ($currentMinute >= $matchDuration && $match->status === 'in_progress') {
                $this->end($match);
            }
        }

        return $currentMinute;
    }

    /**
     * Update minutes for all active matches
     */
    public function updateAllActiveMatches(): int
    {
        $activeMatches = MatchModel::where('status', 'in_progress')
            ->whereNotNull('started_at')
            ->with('tournament.settings')
            ->get();

        $updated = 0;
        foreach ($activeMatches as $match) {
            try {
                $oldMinute = $match->current_minute;
                $oldStatus = $match->status;
                $newMinute = $this->updateMinute($match);
                $match->refresh();

                if ($oldMinute !== $newMinute || $oldStatus !== $match->status) {
                    $updated++;
                }
            } catch (\Exception $e) {
                Log::error("Error updating minute for match {$match->id}: " . $e->getMessage());
            }
        }

        return $updated;
    }

    /**
     * Get elapsed time in seconds for a match
     */
    public function getElapsedSeconds(MatchModel $match): int
    {
        if (!$match->started_at) {
            return 0;
        }

        $elapsedSeconds = now()->diffInSeconds($match->started_at);
        $pausedSeconds = $match->total_paused_seconds ?? 0;

        return max(0, $elapsedSeconds - $pausedSeconds);
    }

    /**
     * Get remaining time in seconds for a match
     */
    public function getRemainingSeconds(MatchModel $match): ?int
    {
        if ($match->status !== 'in_progress') {
            return null;
        }

        $matchDuration = $match->tournament->settings->match_duration ?? 90;
        $elapsedSeconds = $this->getElapsedSeconds($match);
        $totalSeconds = $matchDuration * 60;

        return max(0, $totalSeconds - $elapsedSeconds);
    }
}
