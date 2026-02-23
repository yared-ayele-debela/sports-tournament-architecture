<?php

namespace App\Services\Events\Handlers;

use App\Contracts\BaseEventHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Tournament Event Handler
 * 
 * Handles tournament-related events for the Team Service
 * - Caches tournament information for validation
 * - Prevents team changes in completed tournaments
 */
class TournamentEventHandler extends BaseEventHandler
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
     * Process the event
     *
     * @param array $event
     * @return void
     */
    protected function processEvent(array $event): void
    {
        $eventType = $event['event_type'];
        $payload = $event['payload'];

        switch ($eventType) {
            case 'tournament.created':
                $this->handleTournamentCreated($payload);
                break;

            case 'tournament.updated':
                $this->handleTournamentUpdated($payload);
                break;

            case 'tournament.status.changed':
                $this->handleTournamentStatusChanged($payload);
                break;

            case 'tournament.deleted':
                $this->handleTournamentDeleted($payload);
                break;

            default:
                Log::warning('Unknown tournament event type', [
                    'event_type' => $eventType,
                    'event_id' => $event['event_id']
                ]);
        }
    }

    /**
     * Get the event types this handler can handle
     *
     * @return array
     */
    public function getHandledEventTypes(): array
    {
        return [
            'tournament.created',
            'tournament.updated',
            'tournament.status.changed',
            'tournament.deleted'
        ];
    }

    /**
     * Handle tournament created event
     *
     * @param array $payload
     * @return void
     */
    protected function handleTournamentCreated(array $payload): void
    {
        $tournamentId = $payload['tournament_id'] ?? null;
        
        if (!$tournamentId) {
            Log::warning('Invalid tournament created payload - missing tournament_id', [
                'payload' => $payload
            ]);
            return;
        }

        // Cache tournament data for validation
        $this->cacheTournamentData($tournamentId, $payload);

        Log::info('Tournament created event processed', [
            'tournament_id' => $tournamentId,
            'name' => $payload['name'] ?? 'Unknown'
        ]);
    }

    /**
     * Handle tournament updated event
     *
     * @param array $payload
     * @return void
     */
    protected function handleTournamentUpdated(array $payload): void
    {
        $tournamentId = $payload['tournament_id'] ?? null;
        
        if (!$tournamentId) {
            Log::warning('Invalid tournament updated payload - missing tournament_id', [
                'payload' => $payload
            ]);
            return;
        }

        // Update cached tournament data
        $this->cacheTournamentData($tournamentId, $payload);

        Log::info('Tournament updated event processed', [
            'tournament_id' => $tournamentId,
            'name' => $payload['name'] ?? 'Unknown',
            'updated_fields' => $payload['updated_fields'] ?? []
        ]);
    }

    /**
     * Handle tournament status changed event
     *
     * @param array $payload
     * @return void
     */
    protected function handleTournamentStatusChanged(array $payload): void
    {
        $tournamentId = $payload['tournament_id'] ?? null;
        $oldStatus = $payload['old_status'] ?? null;
        $newStatus = $payload['new_status'] ?? null;
        
        if (!$tournamentId || !$oldStatus || !$newStatus) {
            Log::warning('Invalid tournament status changed payload', [
                'payload' => $payload
            ]);
            return;
        }

        // Update cached tournament status
        $cacheKey = $this->getCacheKey($tournamentId);
        $cachedData = Cache::get($cacheKey, []);
        
        if ($cachedData) {
            $cachedData['status'] = $newStatus;
            Cache::put($cacheKey, $cachedData, self::CACHE_TTL);
        }

        // If tournament is completed, prevent team changes
        if ($newStatus === 'completed') {
            $this->lockTournamentTeams($tournamentId);
        } elseif ($oldStatus === 'completed' && $newStatus !== 'completed') {
            // Tournament reopened - unlock teams
            $this->unlockTournamentTeams($tournamentId);
        }

        Log::info('Tournament status changed event processed', [
            'tournament_id' => $tournamentId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'transition_reason' => $payload['transition_reason'] ?? 'unknown'
        ]);
    }

    /**
     * Handle tournament deleted event
     *
     * @param array $payload
     * @return void
     */
    protected function handleTournamentDeleted(array $payload): void
    {
        $tournamentId = $payload['tournament_id'] ?? null;
        
        if (!$tournamentId) {
            Log::warning('Invalid tournament deleted payload - missing tournament_id', [
                'payload' => $payload
            ]);
            return;
        }

        // Remove cached tournament data
        $this->removeCachedTournamentData($tournamentId);

        Log::info('Tournament deleted event processed', [
            'tournament_id' => $tournamentId,
            'name' => $payload['name'] ?? 'Unknown'
        ]);
    }

    /**
     * Cache tournament data for validation
     *
     * @param int $tournamentId
     * @param array $payload
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
            'cached_at' => now()->toISOString()
        ];

        Cache::put($cacheKey, $tournamentData, self::CACHE_TTL);

        Log::debug('Tournament data cached', [
            'tournament_id' => $tournamentId,
            'cache_key' => $cacheKey
        ]);
    }

    /**
     * Remove cached tournament data
     *
     * @param int $tournamentId
     * @return void
     */
    protected function removeCachedTournamentData(int $tournamentId): void
    {
        $cacheKey = $this->getCacheKey($tournamentId);
        Cache::forget($cacheKey);

        Log::debug('Tournament data removed from cache', [
            'tournament_id' => $tournamentId,
            'cache_key' => $cacheKey
        ]);
    }

    /**
     * Lock teams in completed tournament
     *
     * @param int $tournamentId
     * @return void
     */
    protected function lockTournamentTeams(int $tournamentId): void
    {
        try {
            // Update teams to mark as locked
            DB::table('teams')
                ->where('tournament_id', $tournamentId)
                ->update(['locked' => true, 'locked_at' => now()]);

            Log::info('Teams locked for completed tournament', [
                'tournament_id' => $tournamentId
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to lock tournament teams', [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Unlock teams when tournament is reopened
     *
     * @param int $tournamentId
     * @return void
     */
    protected function unlockTournamentTeams(int $tournamentId): void
    {
        try {
            // Update teams to mark as unlocked
            DB::table('teams')
                ->where('tournament_id', $tournamentId)
                ->update(['locked' => false, 'unlocked_at' => now()]);

            Log::info('Teams unlocked for reopened tournament', [
                'tournament_id' => $tournamentId
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to unlock tournament teams', [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cache key for tournament
     *
     * @param int $tournamentId
     * @return string
     */
    protected function getCacheKey(int $tournamentId): string
    {
        return self::CACHE_PREFIX . $tournamentId;
    }

    /**
     * Get cached tournament data
     *
     * @param int $tournamentId
     * @return array|null
     */
    public function getCachedTournament(int $tournamentId): ?array
    {
        $cacheKey = $this->getCacheKey($tournamentId);
        return Cache::get($cacheKey);
    }

    /**
     * Check if tournament is completed
     *
     * @param int $tournamentId
     * @return bool
     */
    public function isTournamentCompleted(int $tournamentId): bool
    {
        $tournamentData = $this->getCachedTournament($tournamentId);
        return $tournamentData ? ($tournamentData['status'] === 'completed') : false;
    }

    /**
     * Validate tournament exists and is accessible
     *
     * @param int $tournamentId
     * @return bool
     */
    public function validateTournament(int $tournamentId): bool
    {
        $tournamentData = $this->getCachedTournament($tournamentId);
        return $tournamentData !== null;
    }
}
