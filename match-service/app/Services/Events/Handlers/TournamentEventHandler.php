<?php

namespace App\Services\Events\Handlers;

use App\Contracts\BaseEventHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Tournament Event Handler
 * 
 * Handles tournament-related events for the Match Service
 * - Caches tournament information for validation
 * - Prevents match scheduling in completed tournaments
 * - Manages match scheduling based on tournament status
 */
class TournamentEventHandler extends BaseEventHandler
{
    /**
     * Cache key prefix for tournament data
     */
    const CACHE_PREFIX = 'tournament:';

    /**
     * Cache TTL in seconds (2 hours)
     */
    const CACHE_TTL = 7200;

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
            case 'sports.tournament.created':
                $this->handleTournamentCreated($payload);
                break;

            case 'sports.tournament.updated':
                $this->handleTournamentUpdated($payload);
                break;

            case 'sports.tournament.status.changed':
                $this->handleTournamentStatusChanged($payload);
                break;

            case 'sports.tournament.deleted':
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
            'sports.tournament.created',
            'sports.tournament.updated',
            'sports.tournament.status.changed',
            'sports.tournament.deleted'
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

        // Handle tournament status changes for matches
        $this->handleTournamentStatusChangeForMatches($tournamentId, $oldStatus, $newStatus);

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

        // Cancel all scheduled matches for this tournament
        $this->cancelTournamentMatches($tournamentId);

        Log::info('Tournament deleted event processed', [
            'tournament_id' => $tournamentId,
            'name' => $payload['name'] ?? 'Unknown'
        ]);
    }

    /**
     * Handle tournament status change for matches
     *
     * @param int $tournamentId
     * @param string $oldStatus
     * @param string $newStatus
     * @return void
     */
    protected function handleTournamentStatusChangeForMatches(int $tournamentId, string $oldStatus, string $newStatus): void
    {
        try {
            switch ($newStatus) {
                case 'completed':
                    $this->handleTournamentCompleted($tournamentId);
                    break;
                
                case 'cancelled':
                    $this->handleTournamentCancelled($tournamentId);
                    break;
                
                case 'ongoing':
                    $this->handleTournamentOngoing($tournamentId);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Failed to handle tournament status change for matches', [
                'tournament_id' => $tournamentId,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle tournament completion
     *
     * @param int $tournamentId
     * @return void
     */
    protected function handleTournamentCompleted(int $tournamentId): void
    {
        // Cancel all scheduled matches
        $affectedMatches = \App\Models\MatchGame::where('tournament_id', $tournamentId)
            ->where('status', 'scheduled')
            ->update(['status' => 'cancelled']);

        Log::info('Tournament completed - scheduled matches cancelled', [
            'tournament_id' => $tournamentId,
            'cancelled_matches' => $affectedMatches
        ]);
    }

    /**
     * Handle tournament cancellation
     *
     * @param int $tournamentId
     * @return void
     */
    protected function handleTournamentCancelled(int $tournamentId): void
    {
        // Cancel all scheduled and in-progress matches
        $affectedMatches = \App\Models\MatchGame::where('tournament_id', $tournamentId)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->update(['status' => 'cancelled']);

        Log::info('Tournament cancelled - matches cancelled', [
            'tournament_id' => $tournamentId,
            'cancelled_matches' => $affectedMatches
        ]);
    }

    /**
     * Handle tournament ongoing status
     *
     * @param int $tournamentId
     * @return void
     */
    protected function handleTournamentOngoing(int $tournamentId): void
    {
        // Allow scheduled matches to proceed
        // This could trigger match scheduling if needed
        Log::info('Tournament ongoing - matches can proceed', [
            'tournament_id' => $tournamentId
        ]);
    }

    /**
     * Cancel all matches for a tournament
     *
     * @param int $tournamentId
     * @return void
     */
    protected function cancelTournamentMatches(int $tournamentId): void
    {
        try {
            $affectedMatches = \App\Models\MatchGame::where('tournament_id', $tournamentId)
                ->whereIn('status', ['scheduled', 'in_progress'])
                ->update(['status' => 'cancelled']);

            Log::info('All tournament matches cancelled due to tournament deletion', [
                'tournament_id' => $tournamentId,
                'cancelled_matches' => $affectedMatches
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cancel tournament matches', [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage()
            ]);
        }
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
     * Check if tournament is cancelled
     *
     * @param int $tournamentId
     * @return bool
     */
    public function isTournamentCancelled(int $tournamentId): bool
    {
        $tournamentData = $this->getCachedTournament($tournamentId);
        return $tournamentData ? ($tournamentData['status'] === 'cancelled') : false;
    }

    /**
     * Validate tournament exists and is accessible for match scheduling
     *
     * @param int $tournamentId
     * @return bool
     */
    public function validateTournamentForMatchScheduling(int $tournamentId): bool
    {
        $tournamentData = $this->getCachedTournament($tournamentId);
        
        if (!$tournamentData) {
            return false;
        }

        // Prevent scheduling in completed or cancelled tournaments
        $blockedStatuses = ['completed', 'cancelled'];
        return !in_array($tournamentData['status'], $blockedStatuses);
    }
}
