<?php

namespace App\Services\Events\Handlers;

use App\Contracts\EventHandlerInterface;
use App\Services\PublicCacheService;
use App\Models\Standing;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Cache Invalidation Handler for Results Service
 *
 * Handles cache invalidation events from queue.
 * Listens to standings and match events and invalidates public API cache by tags.
 */
class CacheInvalidationHandler implements EventHandlerInterface
{
    protected PublicCacheService $cacheService;

    public function __construct(PublicCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle cache invalidation event
     *
     * @param array $event Event data
     * @return void
     */
    public function handle(array $event): void
    {
        try {
            $eventType = $event['event_type'] ?? null;
            $payload = $event['payload'] ?? [];

            if (!$eventType) {
                Log::warning('Cache invalidation event missing event_type', ['event' => $event]);
                return;
            }

            // Map event types to cache tags, patterns, and specific keys
            $tags = $this->mapEventToTags($eventType, $payload);
            $patterns = $this->mapEventToPatterns($eventType, $payload);
            $keys = $this->mapEventToKeys($eventType, $payload);

            if (empty($tags) && empty($patterns) && empty($keys)) {
                Log::debug('No cache tags, patterns, or keys mapped for event', ['event_type' => $eventType]);
                return;
            }

            // Invalidate cache by specific keys first (most targeted)
            if (!empty($keys)) {
                foreach ($keys as $key) {
                    $result = $this->cacheService->forget($key);
                    // Note: false can mean key didn't exist (which is fine) or an error occurred
                    // We log it but don't treat it as a failure
                    if ($result) {
                        Log::debug('Cache key invalidated from event', [
                            'event_type' => $eventType,
                            'key' => $key,
                        ]);
                    } else {
                        Log::debug('Cache key not found (may not exist)', [
                            'event_type' => $eventType,
                            'key' => $key,
                        ]);
                    }
                }
            }

            // Invalidate cache by tags
            if (!empty($tags)) {
                $result = $this->cacheService->forgetByTags($tags);
                Log::info('Cache invalidated by tags from event', [
                    'event_type' => $eventType,
                    'tags' => $tags,
                    'success' => $result,
                ]);
            }

            // Invalidate cache by patterns (for wildcard matching)
            if (!empty($patterns)) {
                foreach ($patterns as $pattern) {
                    $deleted = $this->cacheService->forgetByPattern($pattern);
                    Log::info('Cache invalidated by pattern from event', [
                        'event_type' => $eventType,
                        'pattern' => $pattern,
                        'keys_deleted' => $deleted,
                    ]);
                }
            }

            // Also invalidate internal Redis keys directly (for keys set with Redis::setex)
            $this->invalidateInternalRedisKeys($eventType, $payload);

        } catch (\Exception $e) {
            Log::error('Failed to handle cache invalidation event', [
                'event' => $event,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e; // Re-throw to let queue system handle retries
        }
    }

    /**
     * Check if handler can handle the event type
     *
     * @param string $eventType
     * @return bool
     */
    public function canHandle(string $eventType): bool
    {
        $handledTypes = $this->getHandledEventTypes();
        return in_array($eventType, $handledTypes) || $this->isGenericEventType($eventType);
    }

    /**
     * Get list of event types this handler handles
     *
     * @return array
     */
    public function getHandledEventTypes(): array
    {
        return [
            // Standings Events (local)
            'standings.updated',
            'sports.standings.updated',
            'standings.recalculated',
            'sports.standings.recalculated',
            // Statistics Events (local)
            'statistics.updated',
            'sports.statistics.updated',
            // Match Events (from match-service)
            'match.completed',
            'sports.match.completed',
            'match.event.recorded',
            // Tournament Events (from tournament-service)
            'tournament.updated',
            'tournament.status.changed',
            'sports.tournament.updated',
            'sports.tournament.status.changed',
        ];
    }

    /**
     * Map event type to cache tags
     *
     * @param string $eventType
     * @param array $payload
     * @return array
     */
    protected function mapEventToTags(string $eventType, array $payload): array
    {
        $tags = [];

        // Standings Events
        if (str_starts_with($eventType, 'standings.') || str_starts_with($eventType, 'sports.standings.')) {
            $tags = array_merge($tags, $this->getStandingsEventTags($eventType, $payload));
        }

        // Statistics Events
        if (str_starts_with($eventType, 'statistics.') || str_starts_with($eventType, 'sports.statistics.')) {
            $tags = array_merge($tags, $this->getStatisticsEventTags($eventType, $payload));
        }

        // Match Events
        if (str_starts_with($eventType, 'match.')) {
            $tags = array_merge($tags, $this->getMatchEventTags($eventType, $payload));
        }

        // Tournament Events
        if (str_starts_with($eventType, 'tournament.') || str_starts_with($eventType, 'sports.tournament.')) {
            $tags = array_merge($tags, $this->getTournamentEventTags($eventType, $payload));
        }

        return array_unique($tags);
    }

    /**
     * Get cache tags for standings events
     *
     * @param string $eventType
     * @param array $payload
     * @return array
     */
    protected function getStandingsEventTags(string $eventType, array $payload): array
    {
        $tournamentId = $payload['tournament_id'] ?? null;
        $homeTeamId = $payload['home_team_id'] ?? null;
        $awayTeamId = $payload['away_team_id'] ?? null;
        $teamId = $payload['team_id'] ?? null;

        $tags = ['public-api', 'standings', 'public:standings'];

        if ($tournamentId) {
            // Internal cache tags
            $tags[] = "tournament:{$tournamentId}:standings";

            // Public API cache tags
            $tags[] = "public:tournament:{$tournamentId}:standings";
            $tags[] = "public:tournament:{$tournamentId}:statistics";
        }

        // Team standing cache tags
        if ($homeTeamId) {
            $tags[] = "team:{$homeTeamId}:standing";
            $tags[] = "public:team:{$homeTeamId}:standing";
        }
        if ($awayTeamId) {
            $tags[] = "team:{$awayTeamId}:standing";
            $tags[] = "public:team:{$awayTeamId}:standing";
        }
        if ($teamId) {
            $tags[] = "team:{$teamId}:standing";
            $tags[] = "public:team:{$teamId}:standing";
        }

        return $tags;
    }

    /**
     * Get cache tags for statistics events
     *
     * @param string $eventType
     * @param array $payload
     * @return array
     */
    protected function getStatisticsEventTags(string $eventType, array $payload): array
    {
        $tournamentId = $payload['tournament_id'] ?? null;

        $tags = ['public-api', 'statistics', 'public:statistics'];

        if ($tournamentId) {
            // Internal cache tags
            $tags[] = "tournament:{$tournamentId}:statistics";

            // Public API cache tags
            $tags[] = "public:tournament:{$tournamentId}:statistics";
            $tags[] = "public:tournament:{$tournamentId}:scorers";
        }

        return $tags;
    }

    /**
     * Get cache tags for match events
     *
     * @param string $eventType
     * @param array $payload
     * @return array
     */
    protected function getMatchEventTags(string $eventType, array $payload): array
    {
        $tournamentId = $payload['tournament_id'] ?? null;
        $matchId = $payload['match_id'] ?? null;
        $homeTeamId = $payload['home_team_id'] ?? null;
        $awayTeamId = $payload['away_team_id'] ?? null;

        $tags = ['public-api', 'standings', 'public:standings'];

        if ($tournamentId) {
            $tags[] = "public:tournament:{$tournamentId}:standings";
            $tags[] = "public:tournament:{$tournamentId}:statistics";
            $tags[] = "public:tournament:{$tournamentId}:scorers";
        }

        if ($homeTeamId) {
            $tags[] = "public:team:{$homeTeamId}:standing";
        }

        if ($awayTeamId) {
            $tags[] = "public:team:{$awayTeamId}:standing";
        }

        // Match events (goals, cards) affect top scorers
        if ($eventType === 'match.event.recorded' && $tournamentId) {
            $tags[] = "public:tournament:{$tournamentId}:scorers";
        }

        return $tags;
    }

    /**
     * Get cache tags for tournament events
     *
     * @param string $eventType
     * @param array $payload
     * @return array
     */
    protected function getTournamentEventTags(string $eventType, array $payload): array
    {
        $tournamentId = $payload['tournament_id'] ?? $payload['id'] ?? null;

        $tags = ['public-api', 'standings', 'public:standings'];

        if ($tournamentId) {
            $tags[] = "public:tournament:{$tournamentId}:standings";
            $tags[] = "public:tournament:{$tournamentId}:statistics";
            $tags[] = "public:tournament:{$tournamentId}:scorers";
        }

        return $tags;
    }

    /**
     * Map event type to cache key patterns (for wildcard invalidation)
     *
     * @param string $eventType
     * @param array $payload
     * @return array
     */
    protected function mapEventToPatterns(string $eventType, array $payload): array
    {
        $patterns = [];

        // Standings Events
        if (str_starts_with($eventType, 'standings.') || str_starts_with($eventType, 'sports.standings.')) {
            $patterns = array_merge($patterns, $this->getStandingsEventPatterns($eventType, $payload));
        }

        // Statistics Events
        if (str_starts_with($eventType, 'statistics.') || str_starts_with($eventType, 'sports.statistics.')) {
            $patterns = array_merge($patterns, $this->getStatisticsEventPatterns($eventType, $payload));
        }

        return array_unique($patterns);
    }

    /**
     * Get cache key patterns for standings events
     *
     * @param string $eventType
     * @param array $payload
     * @return array
     */
    protected function getStandingsEventPatterns(string $eventType, array $payload): array
    {
        $tournamentId = $payload['tournament_id'] ?? null;
        $patterns = [];

        if ($tournamentId) {
            // Internal cache patterns
            $patterns[] = "tournament_standings:{$tournamentId}*";

            // Public API cache patterns
            $patterns[] = "public_api:tournament:{$tournamentId}:standings*";
            $patterns[] = "public_api:tournament:{$tournamentId}:statistics*";
        }

        return $patterns;
    }

    /**
     * Get cache key patterns for statistics events
     *
     * @param string $eventType
     * @param array $payload
     * @return array
     */
    protected function getStatisticsEventPatterns(string $eventType, array $payload): array
    {
        $tournamentId = $payload['tournament_id'] ?? null;
        $patterns = [];

        if ($tournamentId) {
            // Internal cache patterns
            $patterns[] = "tournament_statistics:{$tournamentId}*";

            // Public API cache patterns
            $patterns[] = "public_api:tournament:{$tournamentId}:statistics*";
            $patterns[] = "public_api:tournament:{$tournamentId}:scorers*";
        }

        return $patterns;
    }

    /**
     * Map event type to specific cache keys (for direct key invalidation)
     *
     * @param string $eventType
     * @param array $payload
     * @return array
     */
    protected function mapEventToKeys(string $eventType, array $payload): array
    {
        $keys = [];

        // Standings Events - invalidate team standing caches
        if (str_starts_with($eventType, 'standings.') || str_starts_with($eventType, 'sports.standings.')) {
            $keys = array_merge($keys, $this->getStandingsEventKeys($eventType, $payload));
        }

        // Statistics Events - invalidate statistics cache
        if (str_starts_with($eventType, 'statistics.') || str_starts_with($eventType, 'sports.statistics.')) {
            $keys = array_merge($keys, $this->getStatisticsEventKeys($eventType, $payload));
        }

        // Match Events - invalidate team standing caches for both teams
        if (str_starts_with($eventType, 'match.')) {
            $keys = array_merge($keys, $this->getMatchEventKeys($eventType, $payload));
        }

        return array_unique($keys);
    }

    /**
     * Get specific cache keys for standings events
     *
     * @param string $eventType
     * @param array $payload
     * @return array
     */
    protected function getStandingsEventKeys(string $eventType, array $payload): array
    {
        $keys = [];
        $tournamentId = $payload['tournament_id'] ?? null;
        $homeTeamId = $payload['home_team_id'] ?? null;
        $awayTeamId = $payload['away_team_id'] ?? null;
        $teamId = $payload['team_id'] ?? null;

        if ($tournamentId) {
            // Internal cache key
            $keys[] = "tournament_standings:{$tournamentId}";

            // Public API cache key
            $keys[] = "public_api:tournament:{$tournamentId}:standings";

            // Invalidate all team standings for this tournament
            try {
                $teamIds = Standing::where('tournament_id', $tournamentId)
                    ->pluck('team_id')
                    ->toArray();

                foreach ($teamIds as $tid) {
                    $keys[] = "public_api:team:{$tid}:standing";
                }
            } catch (\Exception $e) {
                Log::warning('Failed to get team IDs for cache invalidation', [
                    'tournament_id' => $tournamentId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Also invalidate specific team standings if provided
        if ($homeTeamId) {
            $keys[] = "public_api:team:{$homeTeamId}:standing";
        }
        if ($awayTeamId) {
            $keys[] = "public_api:team:{$awayTeamId}:standing";
        }
        if ($teamId) {
            $keys[] = "public_api:team:{$teamId}:standing";
        }

        return $keys;
    }

    /**
     * Get specific cache keys for statistics events
     *
     * @param string $eventType
     * @param array $payload
     * @return array
     */
    protected function getStatisticsEventKeys(string $eventType, array $payload): array
    {
        $keys = [];
        $tournamentId = $payload['tournament_id'] ?? null;

        if ($tournamentId) {
            // Internal cache keys
            $keys[] = "tournament_statistics:{$tournamentId}";

            // Public API cache keys
            $keys[] = "public_api:tournament:{$tournamentId}:statistics";
            $keys[] = "public_api:tournament:{$tournamentId}:scorers";
        }

        return $keys;
    }

    /**
     * Get specific cache keys for match events
     *
     * @param string $eventType
     * @param array $payload
     * @return array
     */
    protected function getMatchEventKeys(string $eventType, array $payload): array
    {
        $keys = [];
        $homeTeamId = $payload['home_team_id'] ?? null;
        $awayTeamId = $payload['away_team_id'] ?? null;

        // Invalidate team standing caches for both teams
        if ($homeTeamId) {
            $keys[] = "public_api:team:{$homeTeamId}:standing";
        }
        if ($awayTeamId) {
            $keys[] = "public_api:team:{$awayTeamId}:standing";
        }

        return $keys;
    }

    /**
     * Check if event type is a generic pattern
     *
     * @param string $eventType
     * @return bool
     */
    /**
     * Invalidate internal Redis keys (set directly with Redis::setex)
     *
     * @param string $eventType
     * @param array $payload
     * @return void
     */
    protected function invalidateInternalRedisKeys(string $eventType, array $payload): void
    {
        try {
            $tournamentId = $payload['tournament_id'] ?? null;

            if (!$tournamentId) {
                return;
            }

            // Invalidate internal cache keys used by StandingsCalculator
            if (str_starts_with($eventType, 'standings.') || str_starts_with($eventType, 'sports.standings.')) {
                $key = "tournament_standings:{$tournamentId}";
                Redis::del($key);
                Log::info('Internal Redis cache key invalidated', [
                    'event_type' => $eventType,
                    'key' => $key,
                ]);
            }

            if (str_starts_with($eventType, 'statistics.') || str_starts_with($eventType, 'sports.statistics.')) {
                $key = "tournament_statistics:{$tournamentId}";
                Redis::del($key);
                Log::info('Internal Redis cache key invalidated', [
                    'event_type' => $eventType,
                    'key' => $key,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to invalidate internal Redis keys', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function isGenericEventType(string $eventType): bool
    {
        $patterns = [
            'standings.*',
            'statistics.*',
            'match.*',
            'tournament.*',
            'sports.standings.*',
            'sports.statistics.*',
            'sports.match.*',
            'sports.tournament.*'
        ];
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $eventType)) {
                return true;
            }
        }
        return false;
    }
}
