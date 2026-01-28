<?php

namespace App\Observers;

use App\Models\MatchModel;
use App\Services\StandingsCalculator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class MatchObserver
{
    protected StandingsCalculator $standingsCalculator;

    public function __construct(StandingsCalculator $standingsCalculator)
    {
        $this->standingsCalculator = $standingsCalculator;
    }

    public function updated(MatchModel $match): void
    {
        // Trigger standings calculation when match is completed
        if ($this->matchWasCompleted($match)) {
            $this->handleMatchCompletion($match);
        }
        
        // Handle match status changes
        if ($match->wasChanged('status')) {
            $this->handleStatusChange($match);
        }
    }

    public function saved(MatchModel $match): void
    {
        // Clear match-related caches whenever a match is saved
        $this->clearMatchCaches($match);
    }

    public function deleted(MatchModel $match): void
    {
        // Recalculate standings when a match is deleted
        if ($match->status === 'completed') {
            $this->handleMatchDeletion($match);
        }
        
        $this->clearMatchCaches($match);
    }

    private function matchWasCompleted(MatchModel $match): bool
    {
        return $match->status === 'completed' && 
               $match->wasChanged(['status', 'home_score', 'away_score']);
    }

    private function handleMatchCompletion(MatchModel $match): void
    {
        try {
            // Calculate standings for the tournament
            $this->standingsCalculator->calculateTournamentStandings($match->tournament);
            
            // Clear all tournament-related caches
            $this->clearTournamentCaches($match->tournament);
            
            // Log the completion
            Log::info('Match completed and standings recalculated', [
                'match_id' => $match->id,
                'tournament_id' => $match->tournament_id,
                'home_team' => $match->homeTeam->name ?? 'Unknown',
                'away_team' => $match->awayTeam->name ?? 'Unknown',
                'score' => "{$match->home_score} - {$match->away_score}",
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to recalculate standings after match completion', [
                'match_id' => $match->id,
                'tournament_id' => $match->tournament_id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    private function handleStatusChange(MatchModel $match): void
    {
        $oldStatus = $match->getOriginal('status');
        $newStatus = $match->status;
        
        // Log important status changes
        if (in_array($newStatus, ['completed', 'cancelled', 'postponed'])) {
            Log::info('Match status changed', [
                'match_id' => $match->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'tournament_id' => $match->tournament_id,
            ]);
        }
        
        // If match was cancelled after being completed, recalculate standings
        if ($oldStatus === 'completed' && $newStatus !== 'completed') {
            $this->standingsCalculator->calculateTournamentStandings($match->tournament);
            $this->clearTournamentCaches($match->tournament);
        }
    }

    private function handleMatchDeletion(MatchModel $match): void
    {
        try {
            // Recalculate standings since a completed match was removed
            $this->standingsCalculator->calculateTournamentStandings($match->tournament);
            
            // Clear tournament caches
            $this->clearTournamentCaches($match->tournament);
            
            Log::info('Match deleted and standings recalculated', [
                'match_id' => $match->id,
                'tournament_id' => $match->tournament_id,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to recalculate standings after match deletion', [
                'match_id' => $match->id,
                'tournament_id' => $match->tournament_id,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    private function clearMatchCaches(MatchModel $match): void
    {
        $cacheKeys = [
            "match_{$match->id}",
            "match_events_{$match->id}",
            "match_report_{$match->id}",
            "team_matches_{$match->home_team_id}",
            "team_matches_{$match->away_team_id}",
            "tournament_matches_{$match->tournament_id}",
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
            
            if (Redis::exists($key)) {
                Redis::del($key);
            }
        }
    }

    private function clearTournamentCaches($tournament): void
    {
        $cacheKeys = [
            "tournament_standings_{$tournament->id}",
            "tournament_teams_{$tournament->id}",
            "tournament_stats_{$tournament->id}",
            "tournament_matches_{$tournament->id}",
            "tournament_{$tournament->id}",
        ];
        
        // Clear Laravel cache
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
        
        // Clear Redis cache
        if (Redis::ping()) {
            $redisKeys = Redis::keys("*{$tournament->id}*");
            if (!empty($redisKeys)) {
                Redis::del($redisKeys);
            }
        }
        
        // Clear all tournament-related tags if using cache tags
        Cache::tags(["tournament_{$tournament->id}"])->flush();
    }

    public function creating(MatchModel $match): void
    {
        // Validate match data before creation
        $this->validateMatchData($match);
    }

    private function validateMatchData(MatchModel $match): void
    {
        if ($match->home_team_id === $match->away_team_id) {
            throw new \InvalidArgumentException('Home team and away team cannot be the same');
        }
        
        if ($match->home_score !== null && $match->status !== 'completed') {
            throw new \InvalidArgumentException('Scores can only be set for completed matches');
        }
        
        if ($match->status === 'completed' && ($match->home_score === null || $match->away_score === null)) {
            throw new \InvalidArgumentException('Completed matches must have both scores set');
        }
    }
}
