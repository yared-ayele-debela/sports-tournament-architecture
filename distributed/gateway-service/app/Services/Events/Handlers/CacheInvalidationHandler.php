<?php

namespace App\Services\Events\Handlers;

use App\Contracts\BaseEventHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Cache Invalidation Event Handler
 * 
 * Handles cache invalidation based on events from other services
 */
class CacheInvalidationHandler extends BaseEventHandler
{
    /**
     * Cache key patterns for different entity types
     */
    const CACHE_PATTERNS = [
        'tournament' => [
            'gateway:tournament:*',
            'gateway:tournaments:*',
            'gateway:tournament_list:*',
            'gateway:search:tournaments:*'
        ],
        'team' => [
            'gateway:team:*',
            'gateway:teams:*',
            'gateway:team_list:*',
            'gateway:search:teams:*'
        ],
        'player' => [
            'gateway:player:*',
            'gateway:players:*',
            'gateway:team_players:*',
            'gateway:search:players:*'
        ],
        'match' => [
            'gateway:match:*',
            'gateway:matches:*',
            'gateway:tournament_matches:*',
            'gateway:team_matches:*',
            'gateway:search:matches:*'
        ],
        'standings' => [
            'gateway:standings:*',
            'gateway:tournament_standings:*',
            'gateway:search:standings:*'
        ],
        'statistics' => [
            'gateway:statistics:*',
            'gateway:tournament_statistics:*',
            'gateway:team_statistics:*',
            'gateway:search:statistics:*'
        ],
        'sport' => [
            'gateway:sport:*',
            'gateway:sports:*',
            'gateway:sport_list:*',
            'gateway:search:sports:*'
        ],
        'venue' => [
            'gateway:venue:*',
            'gateway:venues:*',
            'gateway:venue_list:*',
            'gateway:search:venues:*'
        ]
    ];

    /**
     * Get the event types this handler can handle
     *
     * @return array
     */
    public function getHandledEventTypes(): array
    {
        return [
            // Tournament events
            'sports.tournament.created',
            'sports.tournament.updated',
            'sports.tournament.deleted',
            'sports.tournament.started',
            'sports.tournament.completed',
            'sports.tournament.status.changed',
            
            // Match events
            'sports.match.created',
            'sports.match.updated',
            'sports.match.deleted',
            'sports.match.started',
            'sports.match.completed',
            'sports.match.status.changed',
            'sports.match.event.recorded',
            
            // Team events
            'sports.team.created',
            'sports.team.updated',
            'sports.team.deleted',
            'sports.player.created',
            'sports.player.updated',
            'sports.player.deleted',
            
            // Results events
            'sports.standings.updated',
            'sports.statistics.updated',
            'sports.tournament.recalculated',
            'sports.standings.recalculated',
            
            // Sport and Venue events
            'sports.sport.created',
            'sports.sport.updated',
            'sports.sport.deleted',
            'sports.venue.created',
            'sports.venue.updated',
            'sports.venue.deleted',
        ];
    }

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
        
        // Determine cache invalidation strategy based on event type
        $invalidationStrategy = $this->getInvalidationStrategy($eventType);
        
        // Execute cache invalidation
        $invalidatedKeys = $this->invalidateCache($invalidationStrategy, $payload);
        
        // Log cache invalidation
        $this->infoLog('Cache invalidation completed', $event, [
            'strategy' => $invalidationStrategy,
            'invalidated_keys_count' => count($invalidatedKeys),
            'invalidated_keys' => $invalidatedKeys
        ]);
        
        // Additional specific invalidations
        $this->handleSpecificInvalidations($eventType, $payload);
    }

    /**
     * Get cache invalidation strategy for event type
     *
     * @param string $eventType
     * @return string
     */
    protected function getInvalidationStrategy(string $eventType): string
    {
        // Map event types to cache invalidation strategies
        $strategies = [
            // Tournament events
            'sports.tournament.created' => 'tournament_all',
            'sports.tournament.updated' => 'tournament_specific',
            'sports.tournament.deleted' => 'tournament_all',
            'sports.tournament.started' => 'tournament_specific',
            'sports.tournament.completed' => 'tournament_all',
            'sports.tournament.status.changed' => 'tournament_all',
            
            // Match events
            'sports.match.created' => 'match_tournament_specific',
            'sports.match.updated' => 'match_specific',
            'sports.match.deleted' => 'match_tournament_specific',
            'sports.match.started' => 'match_specific',
            'sports.match.completed' => 'match_tournament_all',
            'sports.match.status.changed' => 'match_specific',
            'sports.match.event.recorded' => 'match_specific',
            
            // Team events
            'sports.team.created' => 'team_all',
            'sports.team.updated' => 'team_specific',
            'sports.team.deleted' => 'team_all',
            'sports.player.created' => 'team_specific',
            'sports.player.updated' => 'team_specific',
            'sports.player.deleted' => 'team_specific',
            
            // Results events
            'sports.standings.updated' => 'standings_tournament_specific',
            'sports.statistics.updated' => 'statistics_tournament_specific',
            'sports.tournament.recalculated' => 'tournament_all',
            'sports.standings.recalculated' => 'standings_tournament_specific',
            
            // Sport and Venue events
            'sports.sport.created' => 'sport_all',
            'sports.sport.updated' => 'sport_specific',
            'sports.sport.deleted' => 'sport_all',
            'sports.venue.created' => 'venue_all',
            'sports.venue.updated' => 'venue_specific',
            'sports.venue.deleted' => 'venue_all',
        ];

        return $strategies[$eventType] ?? 'none';
    }

    /**
     * Invalidate cache based on strategy
     *
     * @param string $strategy
     * @param array $payload
     * @return array
     */
    protected function invalidateCache(string $strategy, array $payload): array
    {
        $invalidatedKeys = [];

        switch ($strategy) {
            case 'tournament_all':
                $invalidatedKeys = $this->invalidateByPattern(self::CACHE_PATTERNS['tournament']);
                break;

            case 'tournament_specific':
                $tournamentId = $payload['tournament_id'] ?? null;
                if ($tournamentId) {
                    $invalidatedKeys = $this->invalidateTournamentCache($tournamentId);
                }
                break;

            case 'match_tournament_specific':
                $tournamentId = $payload['tournament_id'] ?? null;
                if ($tournamentId) {
                    $invalidatedKeys = array_merge(
                        $this->invalidateTournamentCache($tournamentId),
                        $this->invalidateByPattern(self::CACHE_PATTERNS['match'])
                    );
                }
                break;

            case 'match_tournament_all':
                $tournamentId = $payload['tournament_id'] ?? null;
                if ($tournamentId) {
                    $invalidatedKeys = array_merge(
                        $this->invalidateTournamentCache($tournamentId),
                        $this->invalidateByPattern(self::CACHE_PATTERNS['match']),
                        $this->invalidateByPattern(self::CACHE_PATTERNS['standings']),
                        $this->invalidateByPattern(self::CACHE_PATTERNS['statistics'])
                    );
                }
                break;

            case 'match_specific':
                $matchId = $payload['match_id'] ?? null;
                if ($matchId) {
                    $invalidatedKeys = $this->invalidateMatchCache($matchId);
                }
                break;

            case 'team_all':
                $invalidatedKeys = $this->invalidateByPattern(self::CACHE_PATTERNS['team']);
                break;

            case 'team_specific':
                $teamId = $payload['team_id'] ?? null;
                if ($teamId) {
                    $invalidatedKeys = $this->invalidateTeamCache($teamId);
                }
                break;

            case 'standings_tournament_specific':
                $tournamentId = $payload['tournament_id'] ?? null;
                if ($tournamentId) {
                    $invalidatedKeys = array_merge(
                        $this->invalidateTournamentCache($tournamentId),
                        $this->invalidateByPattern(self::CACHE_PATTERNS['standings'])
                    );
                }
                break;

            case 'statistics_tournament_specific':
                $tournamentId = $payload['tournament_id'] ?? null;
                if ($tournamentId) {
                    $invalidatedKeys = array_merge(
                        $this->invalidateTournamentCache($tournamentId),
                        $this->invalidateByPattern(self::CACHE_PATTERNS['statistics'])
                    );
                }
                break;

            case 'sport_all':
                $invalidatedKeys = $this->invalidateByPattern(self::CACHE_PATTERNS['sport']);
                break;

            case 'sport_specific':
                $sportId = $payload['sport_id'] ?? null;
                if ($sportId) {
                    $invalidatedKeys = $this->invalidateSportCache($sportId);
                }
                break;

            case 'venue_all':
                $invalidatedKeys = $this->invalidateByPattern(self::CACHE_PATTERNS['venue']);
                break;

            case 'venue_specific':
                $venueId = $payload['venue_id'] ?? null;
                if ($venueId) {
                    $invalidatedKeys = $this->invalidateVenueCache($venueId);
                }
                break;
        }

        return $invalidatedKeys;
    }

    /**
     * Invalidate cache by pattern
     *
     * @param array $patterns
     * @return array
     */
    protected function invalidateByPattern(array $patterns): array
    {
        $invalidatedKeys = [];

        foreach ($patterns as $pattern) {
            $keys = Cache::getRedis()->keys($pattern);
            
            if (!empty($keys)) {
                // Remove Redis key prefix if present
                $cleanKeys = array_map(function($key) {
                    $prefix = config('cache.prefix', '');
                    return $prefix ? str_replace($prefix . ':', '', $key) : $key;
                }, $keys);

                Cache::forgetMultiple($cleanKeys);
                $invalidatedKeys = array_merge($invalidatedKeys, $cleanKeys);
            }
        }

        return $invalidatedKeys;
    }

    /**
     * Invalidate tournament-specific cache
     *
     * @param int $tournamentId
     * @return array
     */
    protected function invalidateTournamentCache(int $tournamentId): array
    {
        $patterns = [
            "gateway:tournament:{$tournamentId}",
            "gateway:tournaments:*",
            "gateway:tournament_list:*",
            "gateway:tournament_matches:{$tournamentId}",
            "gateway:tournament_standings:{$tournamentId}",
            "gateway:tournament_statistics:{$tournamentId}",
            "gateway:search:tournaments:*"
        ];

        return $this->invalidateByPattern($patterns);
    }

    /**
     * Invalidate match-specific cache
     *
     * @param int $matchId
     * @return array
     */
    protected function invalidateMatchCache(int $matchId): array
    {
        $patterns = [
            "gateway:match:{$matchId}",
            "gateway:matches:*",
            "gateway:search:matches:*"
        ];

        return $this->invalidateByPattern($patterns);
    }

    /**
     * Invalidate team-specific cache
     *
     * @param int $teamId
     * @return array
     */
    protected function invalidateTeamCache(int $teamId): array
    {
        $patterns = [
            "gateway:team:{$teamId}",
            "gateway:teams:*",
            "gateway:team_list:*",
            "gateway:team_players:{$teamId}",
            "gateway:team_matches:{$teamId}",
            "gateway:team_statistics:{$teamId}",
            "gateway:search:teams:*"
        ];

        return $this->invalidateByPattern($patterns);
    }

    /**
     * Invalidate sport-specific cache
     *
     * @param int $sportId
     * @return array
     */
    protected function invalidateSportCache(int $sportId): array
    {
        $patterns = [
            "gateway:sport:{$sportId}",
            "gateway:sports:*",
            "gateway:sport_list:*",
            "gateway:search:sports:*"
        ];

        return $this->invalidateByPattern($patterns);
    }

    /**
     * Invalidate venue-specific cache
     *
     * @param int $venueId
     * @return array
     */
    protected function invalidateVenueCache(int $venueId): array
    {
        $patterns = [
            "gateway:venue:{$venueId}",
            "gateway:venues:*",
            "gateway:venue_list:*",
            "gateway:search:venues:*"
        ];

        return $this->invalidateByPattern($patterns);
    }

    /**
     * Handle specific invalidations for complex scenarios
     *
     * @param string $eventType
     * @param array $payload
     * @return void
     */
    protected function handleSpecificInvalidations(string $eventType, array $payload): void
    {
        switch ($eventType) {
            case 'sports.tournament.status.changed':
                // When tournament status changes, invalidate all related caches
                $tournamentId = $payload['tournament_id'] ?? null;
                if ($tournamentId) {
                    $this->invalidateByPattern([
                        "gateway:tournament:{$tournamentId}",
                        "gateway:tournament_matches:{$tournamentId}",
                        "gateway:tournament_standings:{$tournamentId}",
                        "gateway:tournament_statistics:{$tournamentId}"
                    ]);
                }
                break;

            case 'sports.match.completed':
                // When match completes, invalidate standings and statistics
                $tournamentId = $payload['tournament_id'] ?? null;
                if ($tournamentId) {
                    $this->invalidateByPattern([
                        "gateway:tournament_standings:{$tournamentId}",
                        "gateway:tournament_statistics:{$tournamentId}"
                    ]);
                }
                break;

            case 'sports.player.updated':
                // When player is updated, invalidate team caches if team changed
                if (isset($payload['old_team_id']) && isset($payload['new_team_id'])) {
                    $this->invalidateTeamCache($payload['old_team_id']);
                    $this->invalidateTeamCache($payload['new_team_id']);
                }
                break;
        }
    }
}
