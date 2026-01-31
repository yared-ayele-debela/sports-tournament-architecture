<?php

namespace App\Handlers;

use App\Services\Queue\BaseEventHandler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Tournament Created Event Handler
 * 
 * Handles tournament.created events:
 * - Caches tournament data locally for validation
 * - Validates tournament exists
 */
class TournamentCreatedHandler extends BaseEventHandler
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
        return ['tournament.created'];
    }

    /**
     * Process the tournament.created event
     *
     * @param array $event Event data structure
     * @return void
     */
    protected function processEvent(array $event): void
    {
        $payload = $event['payload'];
        $tournamentId = $this->getPayloadData($event, 'tournament_id');

        if (!$tournamentId) {
            $this->warningLog('Invalid tournament.created payload - missing tournament_id', $event);
            return;
        }

        // Cache tournament data for validation
        $this->cacheTournamentData($tournamentId, $payload);

        $this->infoLog('Tournament created event processed', $event, [
            'tournament_id' => $tournamentId,
            'name' => $payload['name'] ?? 'Unknown',
        ]);
    }

    /**
     * Cache tournament data for validation
     *
     * @param int $tournamentId Tournament ID
     * @param array $payload Event payload
     * @return void
     */
    protected function cacheTournamentData(int $tournamentId, array $payload): void
    {
        $cacheKey = $this->getCacheKey($tournamentId);
        
        $tournamentData = [
            'id' => $tournamentId,
            'name' => $payload['name'] ?? 'Unknown',
            'status' => $payload['status'] ?? 'unknown',
            'start_date' => $payload['start_date'] ?? null,
            'end_date' => $payload['end_date'] ?? null,
            'sport_id' => $payload['sport_id'] ?? null,
            'created_at' => $payload['created_at'] ?? now()->toISOString(),
            'cached_at' => now()->toISOString(),
        ];

        Cache::put($cacheKey, $tournamentData, self::CACHE_TTL);

        Log::debug('Tournament data cached', [
            'tournament_id' => $tournamentId,
            'cache_key' => $cacheKey,
            'service' => $this->getServiceName(),
        ]);
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
