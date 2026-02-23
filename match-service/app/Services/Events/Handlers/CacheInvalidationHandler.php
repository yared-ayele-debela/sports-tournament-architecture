<?php

namespace App\Services\Events\Handlers;

use App\Contracts\EventHandlerInterface;
use App\Services\PublicCacheService;
use App\Models\MatchGame;
use Illuminate\Support\Facades\Log;

/**
 * Cache Invalidation Handler for Match Service
 *
 * Handles cache invalidation events from queue.
 * Listens to match events and invalidates public API cache by tags.
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
                    Log::info('Cache invalidated by key from event', [
                        'event_type' => $eventType,
                        'key' => $key,
                        'success' => $result,
                    ]);
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
            // Match Events (local)
            'match.created',
            'match.updated',
            'match.completed',
            'match.started',
            'match.status.changed',
            'match.event.recorded',
            'match.event_added',
            'match.score.updated',
            'match.cancelled',
            'match.postponed',
            'match.deleted',
            // Team Events (from team-service)
            'team.created',
            'team.updated',
            'team.deleted',
            // Tournament Events (from tournament-service)
            'tournament.created',
            'tournament.updated',
            'tournament.status.changed',
            'tournament.deleted',
            'sports.tournament.created',
            'sports.tournament.updated',
            'sports.tournament.status.changed',
            'sports.tournament.deleted',
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

        // Match Events
        if (str_starts_with($eventType, 'match.')) {
            $tags = array_merge($tags, $this->getMatchEventTags($eventType, $payload));
        }

        // Team Events
        if (str_starts_with($eventType, 'team.')) {
            $tags = array_merge($tags, $this->getTeamEventTags($eventType, $payload));
        }

        // Tournament Events
        if (str_starts_with($eventType, 'tournament.') || str_starts_with($eventType, 'sports.tournament.')) {
            $tags = array_merge($tags, $this->getTournamentEventTags($eventType, $payload));
        }

        return array_unique($tags);
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
        $matchId = $payload['match_id'] ?? null;
        $tournamentId = $payload['tournament_id'] ?? null;
        $status = $payload['status'] ?? $payload['new_status'] ?? null;

        $tags = ['public-api', 'matches', 'public:matches'];

        // Match-specific tags
        if ($matchId) {
            $tags[] = "match:{$matchId}";
            $tags[] = "public:match:{$matchId}";
        }

        // Tournament-specific tags
        if ($tournamentId) {
            $tags[] = "public:tournament:{$tournamentId}:matches";
        }

        // Event-specific cache invalidation rules
        switch ($eventType) {
            case 'match.event.recorded':
            case 'match.event_added':
                // Real-time invalidation for match events (goals, cards, substitutions)
                if ($matchId) {
                    $tags[] = "match:{$matchId}";
                    $tags[] = "public:match:{$matchId}";
                    $tags[] = "public:match:{$matchId}:events";
                }
                $tags[] = 'public:matches:live';
                if ($tournamentId) {
                    $tags[] = "public:tournament:{$tournamentId}:matches";
                }
                break;

            case 'match.score.updated':
                // Score updates need immediate invalidation
                if ($matchId) {
                    $tags[] = "match:{$matchId}";
                    $tags[] = "public:match:{$matchId}";
                    $tags[] = "public:match:{$matchId}:events";
                }
                $tags[] = 'public:matches:live';
                if ($tournamentId) {
                    $tags[] = "public:tournament:{$tournamentId}:matches";
                }
                break;

            case 'match.started':
                // Match started - invalidate live and today's matches
                if ($matchId) {
                    $tags[] = "match:{$matchId}";
                    $tags[] = "public:match:{$matchId}";
                }
                $tags[] = 'public:matches:live';
                $tags[] = 'public:matches:today';
                break;

            case 'match.status.changed':
                // Status changes affect multiple caches
                $newStatus = $payload['new_status'] ?? $status;
                if ($matchId) {
                    $tags[] = "match:{$matchId}";
                    $tags[] = "public:match:{$matchId}";
                }
                if ($newStatus === 'in_progress') {
                    $tags[] = 'public:matches:live';
                    $tags[] = 'public:matches:today';
                }
                break;

            case 'match.completed':
                // Match completed - invalidate match, live, team matches
                if ($matchId) {
                    $tags[] = "match:{$matchId}";
                    $tags[] = "public:match:{$matchId}";
                }
                $tags[] = 'public:matches:live';
                $tags[] = 'public:matches:today';
                // Team matches will be handled via patterns
                break;

            case 'match.created':
            case 'match.updated':
                // General match updates
                if ($matchId) {
                    $tags[] = "match:{$matchId}";
                    $tags[] = "public:match:{$matchId}";
                }
                if ($tournamentId) {
                    $tags[] = "public:tournament:{$tournamentId}:matches";
                }
                break;

            case 'match.deleted':
            case 'match.cancelled':
            case 'match.postponed':
                // Match removed or changed significantly
                if ($matchId) {
                    $tags[] = "match:{$matchId}";
                    $tags[] = "public:match:{$matchId}";
                }
                if ($tournamentId) {
                    $tags[] = "public:tournament:{$tournamentId}:matches";
                }
                $tags[] = 'public:matches:live';
                $tags[] = 'public:matches:today';
                $tags[] = 'public:matches:upcoming';
                break;
        }

        return array_unique($tags);
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

        // Match Events
        if (str_starts_with($eventType, 'match.')) {
            $patterns = array_merge($patterns, $this->getMatchEventPatterns($eventType, $payload));
        }

        // Tournament Events - invalidate all match caches for this tournament
        if (str_starts_with($eventType, 'tournament.') || str_starts_with($eventType, 'sports.tournament.')) {
            $patterns = array_merge($patterns, $this->getTournamentEventPatterns($eventType, $payload));
        }

        return array_unique($patterns);
    }

    /**
     * Get cache key patterns for match events (wildcard matching)
     *
     * @param string $eventType
     * @param array $payload
     * @return array
     */
    protected function getMatchEventPatterns(string $eventType, array $payload): array
    {
        $matchId = $payload['match_id'] ?? null;
        $homeTeamId = $payload['home_team_id'] ?? null;
        $awayTeamId = $payload['away_team_id'] ?? null;

        $patterns = [];

        switch ($eventType) {
            case 'match.completed':
                // Invalidate all match-related cache for this match
                if ($matchId) {
                    $patterns[] = "public_api:match:{$matchId}:*";
                    $patterns[] = "public_api:public:match:{$matchId}:*";
                }
                // Invalidate team matches cache
                if ($homeTeamId) {
                    $patterns[] = "public_api:public:team:{$homeTeamId}:matches*";
                }
                if ($awayTeamId) {
                    $patterns[] = "public_api:public:team:{$awayTeamId}:matches*";
                }
                break;

            case 'match.event.recorded':
            case 'match.event_added':
            case 'match.score.updated':
                // Invalidate match events cache
                if ($matchId) {
                    $patterns[] = "public_api:public:match:{$matchId}:events*";
                }
                break;
        }

        return $patterns;
    }

    /**
     * Get cache key patterns for tournament events (wildcard matching)
     *
     * @param string $eventType
     * @param array $payload
     * @return array
     */
    protected function getTournamentEventPatterns(string $eventType, array $payload): array
    {
        $tournamentId = $payload['tournament_id'] ?? $payload['id'] ?? null;
        $patterns = [];

        if ($tournamentId) {
            // For tournament updates, we need to invalidate all match details that include tournament data
            // Since match details cache keys are: public_api:match:{match_id}
            // We can't easily invalidate all matches for a tournament without querying DB
            // Instead, we'll use a broader pattern or rely on tags

            // However, we can invalidate the tournament matches list cache pattern
            $patterns[] = "public_api:tournament:{$tournamentId}:matches*";
        }

        return $patterns;
    }

    /**
     * Get cache tags for team events
     *
     * @param string $eventType
     * @param array $payload
     * @return array
     */
    protected function getTeamEventTags(string $eventType, array $payload): array
    {
        $teamId = $payload['team_id'] ?? null;
        $tournamentId = $payload['tournament_id'] ?? null;

        $tags = ['public-api', 'public:matches'];

        if ($tournamentId) {
            $tags[] = "public:tournament:{$tournamentId}:matches";
        }

        // Team changes affect all matches involving that team
        if ($teamId) {
            $tags[] = "public:team:{$teamId}";
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

        $tags = ['public-api', 'public:matches', 'public:tournaments'];

        if ($tournamentId) {
            $tags[] = "public:tournament:{$tournamentId}:matches";
        }

        return $tags;
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

        // Tournament Events - invalidate tournament data cache in TournamentServiceClient
        if (str_starts_with($eventType, 'tournament.') || str_starts_with($eventType, 'sports.tournament.')) {
            $keys = array_merge($keys, $this->getTournamentEventKeys($eventType, $payload));
        }

        return array_unique($keys);
    }

    /**
     * Get specific cache keys for tournament events
     *
     * @param string $eventType
     * @param array $payload
     * @return array
     */
    protected function getTournamentEventKeys(string $eventType, array $payload): array
    {
        $keys = [];
        $tournamentId = $payload['tournament_id'] ?? $payload['id'] ?? null;

        if ($tournamentId) {
            // Invalidate tournament data cache used by TournamentServiceClient
            $keys[] = "public_tournament:{$tournamentId}";

            // Also invalidate venue cache if venue_id is in payload (for venue updates)
            if (isset($payload['venue_id'])) {
                $keys[] = "public_venue:{$payload['venue_id']}";
            }

            // For tournament updates, invalidate all match details caches for this tournament
            // Match details include tournament data, so they need to be invalidated
            if (in_array($eventType, ['tournament.updated', 'sports.tournament.updated'])) {
                try {
                    $matchIds = MatchGame::where('tournament_id', $tournamentId)
                        ->pluck('id')
                        ->toArray();

                    foreach ($matchIds as $matchId) {
                        // Match details cache key: public_api:match:{match_id}
                        $keys[] = "public_api:match:{$matchId}";
                    }

                    Log::info('Invalidating match caches for tournament update', [
                        'tournament_id' => $tournamentId,
                        'match_count' => count($matchIds),
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to get match IDs for tournament cache invalidation', [
                        'tournament_id' => $tournamentId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $keys;
    }

    /**
     * Check if event type is a generic pattern
     *
     * @param string $eventType
     * @return bool
     */
    protected function isGenericEventType(string $eventType): bool
    {
        $patterns = ['match.*', 'team.*', 'tournament.*', 'sports.tournament.*'];
        foreach ($patterns as $pattern) {
            if (fnmatch($pattern, $eventType)) {
                return true;
            }
        }
        return false;
    }
}
