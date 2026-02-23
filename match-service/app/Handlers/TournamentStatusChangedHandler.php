<?php

namespace App\Handlers;

use App\Services\Queue\BaseEventHandler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Tournament Status Changed Event Handler
 * 
 * Handles tournament.status.changed events:
 * - Updates local tournament status cache
 * - Prevents match scheduling in completed tournaments
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
        } else {
            // Create new cache entry if it doesn't exist
            Cache::put($cacheKey, [
                'id' => $tournamentId,
                'status' => $newStatus,
                'status_updated_at' => now()->toISOString(),
                'cached_at' => now()->toISOString(),
            ], self::CACHE_TTL);
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
