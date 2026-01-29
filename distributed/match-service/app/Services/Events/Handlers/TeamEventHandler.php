<?php

namespace App\Services\Events\Handlers;

use App\Contracts\BaseEventHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Team Event Handler
 * 
 * Handles team-related events for the Match Service
 * - Caches team information for validation
 * - Validates team existence for match scheduling
 */
class TeamEventHandler extends BaseEventHandler
{
    /**
     * Cache key prefix for team data
     */
    const CACHE_PREFIX = 'team:';

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
            case 'sports.team.created':
                $this->handleTeamCreated($payload);
                break;

            case 'sports.team.updated':
                $this->handleTeamUpdated($payload);
                break;

            case 'sports.team.deleted':
                $this->handleTeamDeleted($payload);
                break;

            default:
                Log::warning('Unknown team event type', [
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
            'sports.team.created',
            'sports.team.updated',
            'sports.team.deleted'
        ];
    }

    /**
     * Handle team created event
     *
     * @param array $payload
     * @return void
     */
    protected function handleTeamCreated(array $payload): void
    {
        $teamId = $payload['team_id'] ?? null;
        
        if (!$teamId) {
            Log::warning('Invalid team created payload - missing team_id', [
                'payload' => $payload
            ]);
            return;
        }

        // Cache team data for validation
        $this->cacheTeamData($teamId, $payload);

        Log::info('Team created event processed', [
            'team_id' => $teamId,
            'name' => $payload['name'] ?? 'Unknown',
            'tournament_id' => $payload['tournament_id'] ?? null
        ]);
    }

    /**
     * Handle team updated event
     *
     * @param array $payload
     * @return void
     */
    protected function handleTeamUpdated(array $payload): void
    {
        $teamId = $payload['team_id'] ?? null;
        
        if (!$teamId) {
            Log::warning('Invalid team updated payload - missing team_id', [
                'payload' => $payload
            ]);
            return;
        }

        // Update cached team data
        $this->cacheTeamData($teamId, $payload);

        Log::info('Team updated event processed', [
            'team_id' => $teamId,
            'name' => $payload['name'] ?? 'Unknown',
            'updated_fields' => $payload['updated_fields'] ?? []
        ]);
    }

    /**
     * Handle team deleted event
     *
     * @param array $payload
     * @return void
     */
    protected function handleTeamDeleted(array $payload): void
    {
        $teamId = $payload['team_id'] ?? null;
        
        if (!$teamId) {
            Log::warning('Invalid team deleted payload - missing team_id', [
                'payload' => $payload
            ]);
            return;
        }

        // Remove cached team data
        $this->removeCachedTeamData($teamId);

        // Check for any scheduled matches with this team
        $this->handleTeamDeletionInMatches($teamId);

        Log::info('Team deleted event processed', [
            'team_id' => $teamId,
            'name' => $payload['name'] ?? 'Unknown'
        ]);
    }

    /**
     * Cache team data for validation
     *
     * @param int $teamId
     * @param array $payload
     * @return void
     */
    protected function cacheTeamData(int $teamId, array $payload): void
    {
        $cacheKey = $this->getCacheKey($teamId);
        
        $teamData = [
            'id' => $teamId,
            'name' => $payload['name'] ?? 'Unknown',
            'tournament_id' => $payload['tournament_id'] ?? null,
            'coach_id' => $payload['coach_id'] ?? null,
            'logo' => $payload['logo'] ?? null,
            'created_at' => $payload['created_at'] ?? now()->toISOString(),
            'cached_at' => now()->toISOString()
        ];

        Cache::put($cacheKey, $teamData, self::CACHE_TTL);

        Log::debug('Team data cached', [
            'team_id' => $teamId,
            'cache_key' => $cacheKey
        ]);
    }

    /**
     * Remove cached team data
     *
     * @param int $teamId
     * @return void
     */
    protected function removeCachedTeamData(int $teamId): void
    {
        $cacheKey = $this->getCacheKey($teamId);
        Cache::forget($cacheKey);

        Log::debug('Team data removed from cache', [
            'team_id' => $teamId,
            'cache_key' => $cacheKey
        ]);
    }

    /**
     * Handle team deletion in scheduled matches
     *
     * @param int $teamId
     * @return void
     */
    protected function handleTeamDeletionInMatches(int $teamId): void
    {
        try {
            // Find all scheduled matches involving this team
            $matches = \App\Models\MatchGame::where(function ($query) use ($teamId) {
                $query->where('home_team_id', $teamId)
                      ->orWhere('away_team_id', $teamId);
            })->where('status', 'scheduled')->get();

            foreach ($matches as $match) {
                // Cancel matches involving the deleted team
                $match->status = 'cancelled';
                $match->save();

                Log::info('Match cancelled due to team deletion', [
                    'match_id' => $match->id,
                    'deleted_team_id' => $teamId,
                    'home_team_id' => $match->home_team_id,
                    'away_team_id' => $match->away_team_id
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to handle team deletion in matches', [
                'team_id' => $teamId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get cache key for team
     *
     * @param int $teamId
     * @return string
     */
    protected function getCacheKey(int $teamId): string
    {
        return self::CACHE_PREFIX . $teamId;
    }

    /**
     * Get cached team data
     *
     * @param int $teamId
     * @return array|null
     */
    public function getCachedTeam(int $teamId): ?array
    {
        $cacheKey = $this->getCacheKey($teamId);
        return Cache::get($cacheKey);
    }

    /**
     * Validate team exists
     *
     * @param int $teamId
     * @return bool
     */
    public function validateTeam(int $teamId): bool
    {
        $teamData = $this->getCachedTeam($teamId);
        return $teamData !== null;
    }

    /**
     * Validate team belongs to tournament
     *
     * @param int $teamId
     * @param int $tournamentId
     * @return bool
     */
    public function validateTeamTournament(int $teamId, int $tournamentId): bool
    {
        $teamData = $this->getCachedTeam($teamId);
        return $teamData && ($teamData['tournament_id'] === $tournamentId);
    }

    /**
     * Get team name
     *
     * @param int $teamId
     * @return string|null
     */
    public function getTeamName(int $teamId): ?string
    {
        $teamData = $this->getCachedTeam($teamId);
        return $teamData ? $teamData['name'] : null;
    }
}
