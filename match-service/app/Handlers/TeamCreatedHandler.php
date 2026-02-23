<?php

namespace App\Handlers;

use App\Services\Queue\BaseEventHandler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Team Created Event Handler
 * 
 * Handles team.created events:
 * - Caches team data locally for validation
 * - Validates team exists when scheduling matches
 */
class TeamCreatedHandler extends BaseEventHandler
{
    /**
     * Cache key prefix for team data
     */
    const CACHE_PREFIX = 'team:';

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
        return ['team.created'];
    }

    /**
     * Process the team.created event
     *
     * @param array $event Event data structure
     * @return void
     */
    protected function processEvent(array $event): void
    {
        $payload = $event['payload'];
        $teamId = $this->getPayloadData($event, 'team_id');

        if (!$teamId) {
            $this->warningLog('Invalid team.created payload - missing team_id', $event);
            return;
        }

        // Cache team data for validation
        $this->cacheTeamData($teamId, $payload);

        $this->infoLog('Team created event processed', $event, [
            'team_id' => $teamId,
            'name' => $payload['name'] ?? 'Unknown',
            'tournament_id' => $payload['tournament_id'] ?? null,
        ]);
    }

    /**
     * Cache team data for validation
     *
     * @param int $teamId Team ID
     * @param array $payload Event payload
     * @return void
     */
    protected function cacheTeamData(int $teamId, array $payload): void
    {
        $cacheKey = $this->getCacheKey($teamId);
        
        $teamData = [
            'id' => $teamId,
            'name' => $payload['name'] ?? 'Unknown',
            'tournament_id' => $payload['tournament_id'] ?? null,
            'logo' => $payload['logo'] ?? null,
            'coach_id' => $payload['coach_id'] ?? null,
            'created_at' => $payload['created_at'] ?? now()->toISOString(),
            'cached_at' => now()->toISOString(),
        ];

        Cache::put($cacheKey, $teamData, self::CACHE_TTL);

        Log::debug('Team data cached', [
            'team_id' => $teamId,
            'cache_key' => $cacheKey,
            'service' => $this->getServiceName(),
        ]);
    }

    /**
     * Get cache key for team
     *
     * @param int $teamId Team ID
     * @return string
     */
    protected function getCacheKey(int $teamId): string
    {
        return self::CACHE_PREFIX . $teamId;
    }
}
