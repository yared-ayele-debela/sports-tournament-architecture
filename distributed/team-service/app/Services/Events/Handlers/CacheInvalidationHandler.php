<?php

namespace App\Services\Events\Handlers;

use App\Contracts\EventHandlerInterface;
use App\Services\PublicCacheService;
use Illuminate\Support\Facades\Log;

/**
 * Cache Invalidation Handler
 *
 * Handles cache invalidation events from queue.
 * Listens to events and invalidates public API cache by tags.
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

            // Map event types to cache tags
            $tags = $this->mapEventToTags($eventType, $payload);

            if (empty($tags)) {
                Log::debug('No cache tags mapped for event', ['event_type' => $eventType]);
                return;
            }

            // Invalidate cache by tags
            $result = $this->cacheService->forgetByTags($tags);

            Log::info('Cache invalidated from event', [
                'event_type' => $eventType,
                'tags' => $tags,
                'success' => $result,
            ]);
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
            'team.created',
            'team.updated',
            'team.deleted',
            'player.created',
            'player.updated',
            'player.deleted',
            'tournament.created',
            'tournament.updated',
            'tournament.status.changed',
            'tournament.deleted',
            // Generic patterns
            'team.*',
            'player.*',
            'tournament.*',
        ];
    }

    /**
     * Check if event type matches generic patterns
     *
     * @param string $eventType
     * @return bool
     */
    protected function isGenericEventType(string $eventType): bool
    {
        $genericPatterns = ['team', 'player', 'tournament'];
        foreach ($genericPatterns as $pattern) {
            if (str_contains($eventType, $pattern)) {
                return true;
            }
        }
        return false;
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
        $tags = ['public-api'];

        // Team events
        if ($eventType === 'team.created') {
            return $this->getTeamCreatedTags($payload);
        }

        if ($eventType === 'team.updated') {
            return $this->getTeamUpdatedTags($payload);
        }

        if ($eventType === 'team.deleted') {
            return $this->getTeamDeletedTags($payload);
        }

        // Player events
        if ($eventType === 'player.created' || $eventType === 'player.updated' || $eventType === 'player.deleted') {
            return $this->getPlayerEventTags($eventType, $payload);
        }

        // Tournament events (affect team lists)
        if (str_contains($eventType, 'tournament')) {
            $tournamentId = $payload['tournament_id'] ?? $payload['id'] ?? null;
            if ($tournamentId) {
                $tags[] = "tournament:{$tournamentId}";
                $tags[] = "public:tournament:{$tournamentId}:teams";
            }
            $tags[] = 'public:tournaments';
        }

        // Generic team/player events
        if (str_contains($eventType, 'team')) {
            $teamId = $payload['team_id'] ?? $payload['id'] ?? null;
            $tournamentId = $payload['tournament_id'] ?? null;

            if ($teamId) {
                $tags[] = "team:{$teamId}";
                $tags[] = "public:team:{$teamId}";
                $tags[] = "public:team:{$teamId}:players";
                $tags[] = "public:team:{$teamId}:matches";
            }

            if ($tournamentId) {
                $tags[] = "public:tournament:{$tournamentId}:teams";
            }

            $tags[] = 'teams';
        }

        if (str_contains($eventType, 'player')) {
            $teamId = $payload['team_id'] ?? null;
            $playerId = $payload['player_id'] ?? $payload['id'] ?? null;

            if ($teamId) {
                $tags[] = "public:team:{$teamId}:players";
            }

            if ($playerId) {
                $tags[] = "public:player:{$playerId}";
            }

            $tags[] = 'players';
        }

        return array_unique($tags);
    }

    /**
     * Get cache tags for team.created event
     */
    protected function getTeamCreatedTags(array $payload): array
    {
        $tags = ['public-api'];
        $teamId = $payload['team_id'] ?? $payload['id'] ?? null;
        $tournamentId = $payload['tournament_id'] ?? null;

        if ($teamId) {
            $tags[] = "team:{$teamId}";
            $tags[] = "public:team:{$teamId}";
        }

        if ($tournamentId) {
            $tags[] = "public:tournament:{$tournamentId}:teams";
        }

        $tags[] = 'teams';

        return array_unique($tags);
    }

    /**
     * Get cache tags for team.updated event
     */
    protected function getTeamUpdatedTags(array $payload): array
    {
        $tags = ['public-api'];
        $teamId = $payload['team_id'] ?? $payload['id'] ?? null;
        $tournamentId = $payload['tournament_id'] ?? null;

        if ($teamId) {
            $tags[] = "team:{$teamId}";
            $tags[] = "public:team:{$teamId}";
            $tags[] = "public:team:{$teamId}:players";
        }

        if ($tournamentId) {
            $tags[] = "public:tournament:{$tournamentId}:teams";
        }

        $tags[] = 'teams';

        return array_unique($tags);
    }

    /**
     * Get cache tags for team.deleted event
     */
    protected function getTeamDeletedTags(array $payload): array
    {
        $tags = ['public-api'];
        $teamId = $payload['team_id'] ?? $payload['id'] ?? null;
        $tournamentId = $payload['tournament_id'] ?? null;

        if ($teamId) {
            $tags[] = "team:{$teamId}";
            $tags[] = "public:team:{$teamId}";
            $tags[] = "public:team:{$teamId}:players";
            $tags[] = "public:team:{$teamId}:matches";
        }

        if ($tournamentId) {
            $tags[] = "public:tournament:{$tournamentId}:teams";
        }

        $tags[] = 'teams';

        return array_unique($tags);
    }

    /**
     * Get cache tags for player events
     */
    protected function getPlayerEventTags(string $eventType, array $payload): array
    {
        $tags = ['public-api'];
        $teamId = $payload['team_id'] ?? null;
        $playerId = $payload['player_id'] ?? $payload['id'] ?? null;
        $tournamentId = $payload['tournament_id'] ?? null;

        if ($teamId) {
            $tags[] = "public:team:{$teamId}:players";
            $tags[] = "public:team:{$teamId}";
        }

        if ($playerId) {
            $tags[] = "public:player:{$playerId}";
        }

        if ($tournamentId) {
            $tags[] = "public:tournament:{$tournamentId}:teams";
        }

        $tags[] = 'players';
        $tags[] = 'teams';

        return array_unique($tags);
    }
}
