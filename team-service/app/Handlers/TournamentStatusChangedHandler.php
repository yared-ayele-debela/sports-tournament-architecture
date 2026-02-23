<?php

namespace App\Handlers;

use App\Services\Queue\BaseEventHandler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Tournament Status Changed Event Handler
 * 
 * Handles tournament.status.changed events:
 * - Updates local tournament status cache
 * - Prevents modifications to completed tournaments (locks teams)
 */
class TournamentStatusChangedHandler extends BaseEventHandler
{
    /**
     * Cache key prefix for tournament data
     */
    const CACHE_PREFIX = 'tournament:';

    /**
     * Cache TTL in seconds (1 hour)
     */
    const CACHE_TTL = 3600;

    /**
     * Get the event types this handler can handle
     *
     * @return array
     */
    public function getHandledEventTypes(): array
    {
        return ['tournament.status.changed'];
    }

    /**
     * Process the tournament.status.changed event
     *
     * @param array $event Event data structure
     * @return void
     */
    protected function processEvent(array $event): void
    {
        $payload = $event['payload'];
        $tournamentId = $this->getPayloadData($event, 'tournament_id');
        $oldStatus = $this->getPayloadData($event, 'old_status');
        $newStatus = $this->getPayloadData($event, 'new_status');

        if (!$tournamentId || !$oldStatus || !$newStatus) {
            $this->warningLog('Invalid tournament.status.changed payload', $event);
            return;
        }

        // Update cached tournament status
        $this->updateCachedTournamentStatus($tournamentId, $newStatus);

        // If tournament is completed, prevent team changes
        if ($newStatus === 'completed') {
            $this->lockTournamentTeams($tournamentId);
        } elseif ($oldStatus === 'completed' && $newStatus !== 'completed') {
            // Tournament reopened - unlock teams
            $this->unlockTournamentTeams($tournamentId);
        }

        $this->infoLog('Tournament status changed event processed', $event, [
            'tournament_id' => $tournamentId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);
    }

    /**
     * Update cached tournament status
     *
     * @param int $tournamentId Tournament ID
     * @param string $newStatus New status
     * @return void
     */
    protected function updateCachedTournamentStatus(int $tournamentId, string $newStatus): void
    {
        $cacheKey = $this->getCacheKey($tournamentId);
        $cachedData = Cache::get($cacheKey, []);
        
        if ($cachedData) {
            $cachedData['status'] = $newStatus;
            $cachedData['status_updated_at'] = now()->toISOString();
            Cache::put($cacheKey, $cachedData, self::CACHE_TTL);
        }
    }

    /**
     * Lock teams in completed tournament
     *
     * @param int $tournamentId Tournament ID
     * @return void
     */
    protected function lockTournamentTeams(int $tournamentId): void
    {
        try {
            // Update teams to mark as locked (if locked column exists)
            // If locked column doesn't exist, we can add a migration or use a different approach
            $hasLockedColumn = DB::getSchemaBuilder()->hasColumn('teams', 'locked');
            
            if ($hasLockedColumn) {
                DB::table('teams')
                    ->where('tournament_id', $tournamentId)
                    ->update([
                        'locked' => true,
                        'locked_at' => now(),
                    ]);
            }

            $this->infoLog('Teams locked for completed tournament', [], [
                'tournament_id' => $tournamentId,
            ]);
        } catch (\Exception $e) {
            $this->errorLog('Failed to lock tournament teams', [], [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Unlock teams when tournament is reopened
     *
     * @param int $tournamentId Tournament ID
     * @return void
     */
    protected function unlockTournamentTeams(int $tournamentId): void
    {
        try {
            $hasLockedColumn = DB::getSchemaBuilder()->hasColumn('teams', 'locked');
            
            if ($hasLockedColumn) {
                DB::table('teams')
                    ->where('tournament_id', $tournamentId)
                    ->update([
                        'locked' => false,
                        'unlocked_at' => now(),
                    ]);
            }

            $this->infoLog('Teams unlocked for reopened tournament', [], [
                'tournament_id' => $tournamentId,
            ]);
        } catch (\Exception $e) {
            $this->errorLog('Failed to unlock tournament teams', [], [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get cache key for tournament
     *
     * @param int $tournamentId Tournament ID
     * @return string
     */
    protected function getCacheKey(int $tournamentId): string
    {
        return self::CACHE_PREFIX . $tournamentId;
    }
}
