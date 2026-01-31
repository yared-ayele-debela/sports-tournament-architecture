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
                Log::warning('Cache invalidation event missing event_type', [
                    'event' => $event,
                ]);
                return; // Exit early if no event type
            }

            // Map event types to cache tags
            $tags = $this->mapEventToTags($eventType, $payload);

            if (empty($tags)) {
                Log::debug('No cache tags mapped for event', [
                    'event_type' => $eventType,
                ]);
                return; // Not an error, just no cache to invalidate
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

            // Re-throw to let queue system handle retries
            throw $e;
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
            'tournament.created',
            'tournament.updated',
            'tournament.status.changed',
            'tournament.deleted',
            'tournament.settings.updated',
            'match.created',
            'match.updated',
            'match.completed',
            'match.score.updated',
            'team.created',
            'team.updated',
            'team.deleted',
            'standings.updated',
            'statistics.updated',
            'venue.created',
            'venue.updated',
            'venue.deleted',
            'sport.created',
            'sport.updated',
            'sport.deleted',
            // Generic patterns (handled by isGenericEventType)
            'tournament.*',
            'match.*',
            'team.*',
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
        $genericPatterns = [
            'tournament',
            'match',
            'team',
            'standings',
            'results',
            'statistics',
            'venue',
            'sport',
        ];

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
        $tags = ['public-api']; // Always include base tag

        // Tournament-specific event handling with detailed invalidation rules
        if ($eventType === 'tournament.created') {
            return $this->getTournamentCreatedTags($payload);
        }

        if ($eventType === 'tournament.updated') {
            return $this->getTournamentUpdatedTags($payload);
        }

        if ($eventType === 'tournament.status.changed') {
            return $this->getTournamentStatusChangedTags($payload);
        }

        if ($eventType === 'tournament.settings.updated') {
            return $this->getTournamentSettingsUpdatedTags($payload);
        }

        if ($eventType === 'tournament.deleted') {
            return $this->getTournamentDeletedTags($payload);
        }

        // Generic tournament events (fallback)
        if (str_contains($eventType, 'tournament')) {
            $tournamentId = $payload['tournament_id'] ?? $payload['id'] ?? null;

            if ($tournamentId) {
                $tags[] = "tournament:{$tournamentId}";
                $tags[] = "public:tournament:{$tournamentId}";
            }

            $tags[] = 'tournaments';
            $tags[] = 'tournaments:list';
            $tags[] = 'public:tournaments:list';
        }

        // Match events
        if (str_contains($eventType, 'match')) {
            $matchId = $payload['match_id'] ?? $payload['id'] ?? null;
            $tournamentId = $payload['tournament_id'] ?? null;

            if ($matchId) {
                $tags[] = "match:{$matchId}";
            }

            if ($tournamentId) {
                $tags[] = "tournament:{$tournamentId}";
                $tags[] = "tournament:{$tournamentId}:matches";
            }

            // Check if match is live
            if (isset($payload['status']) && $payload['status'] === 'in_progress') {
                $tags[] = 'matches:live';
            }

            $tags[] = 'matches'; // Invalidate all match lists
        }

        // Team events
        if (str_contains($eventType, 'team')) {
            $teamId = $payload['team_id'] ?? $payload['id'] ?? null;
            $tournamentId = $payload['tournament_id'] ?? null;

            if ($teamId) {
                $tags[] = "team:{$teamId}";
            }

            if ($tournamentId) {
                $tags[] = "tournament:{$tournamentId}";
                $tags[] = "tournament:{$tournamentId}:teams";
            }

            $tags[] = 'teams'; // Invalidate all team lists
        }

        // Standings events
        if (str_contains($eventType, 'standings') || str_contains($eventType, 'results')) {
            $tournamentId = $payload['tournament_id'] ?? null;

            if ($tournamentId) {
                $tags[] = "tournament:{$tournamentId}";
                $tags[] = "standings:{$tournamentId}";
            }

            $tags[] = 'standings';
        }

        // Statistics events
        if (str_contains($eventType, 'statistics') || str_contains($eventType, 'stats')) {
            $tournamentId = $payload['tournament_id'] ?? null;

            if ($tournamentId) {
                $tags[] = "tournament:{$tournamentId}";
                $tags[] = "statistics:{$tournamentId}";
            }

            $tags[] = 'statistics';
        }

        // Venue events
        if ($eventType === 'venue.deleted') {
            return $this->getVenueDeletedTags($payload);
        }

        if (str_contains($eventType, 'venue')) {
            $venueId = $payload['venue_id'] ?? $payload['id'] ?? null;

            if ($venueId) {
                $tags[] = "venue:{$venueId}";
                $tags[] = "public:venue:{$venueId}";
            }

            // Invalidate public venues list cache
            $tags[] = 'venues';
            $tags[] = 'venues:list';
            $tags[] = 'public:venues:list';
        }

        // Sport events
        if ($eventType === 'sport.deleted') {
            return $this->getSportDeletedTags($payload);
        }

        if (str_contains($eventType, 'sport')) {
            $sportId = $payload['sport_id'] ?? $payload['id'] ?? null;

            if ($sportId) {
                $tags[] = "sport:{$sportId}";
                $tags[] = "public:sport:{$sportId}";
            }

            // Invalidate public sports list cache
            $tags[] = 'sports';
            $tags[] = 'sports:list';
            $tags[] = 'public:sports:list';
        }

        // Remove duplicates and return
        return array_unique($tags);
    }

    /**
     * Get cache tags for tournament.created event
     *
     * @param array $payload
     * @return array
     */
    protected function getTournamentCreatedTags(array $payload): array
    {
        $tags = ['public-api'];

        // Existing/internal cache tags
        $tags[] = 'tournaments:list';
        $tags[] = 'tournaments:active';

        // Public API cache tags
        $tags[] = 'public:tournaments:list';
        $tags[] = 'public:tournaments:featured';
        $tags[] = 'public:tournaments:upcoming';

        // Also invalidate general tournament tags
        $tags[] = 'tournaments';

        // If tournament is already public (ongoing/completed), invalidate featured
        $status = $payload['status'] ?? null;
        if (in_array($status, ['ongoing', 'completed'])) {
            $tags[] = 'tournaments:featured';
        }

        // If tournament is planned, invalidate upcoming
        if ($status === 'planned') {
            $tags[] = 'tournaments:upcoming';
        }

        return array_unique($tags);
    }

    /**
     * Get cache tags for tournament.updated event
     *
     * @param array $payload
     * @return array
     */
    protected function getTournamentUpdatedTags(array $payload): array
    {
        $tags = ['public-api'];

        $tournamentId = $payload['tournament_id'] ?? $payload['id'] ?? null;

        if ($tournamentId) {
            // Specific tournament cache
            $tags[] = "tournament:{$tournamentId}";
            $tags[] = "public:tournament:{$tournamentId}";
        }

        // Public API list cache (tournament might appear in lists)
        $tags[] = 'public:tournaments:list';
        $tags[] = 'tournaments:list';

        // General tournament tags
        $tags[] = 'tournaments';

        return array_unique($tags);
    }

    /**
     * Get cache tags for tournament.status.changed event
     *
     * @param array $payload
     * @return array
     */
    protected function getTournamentStatusChangedTags(array $payload): array
    {
        $tags = ['public-api'];

        $tournamentId = $payload['tournament_id'] ?? $payload['id'] ?? null;
        $newStatus = $payload['new_status'] ?? $payload['status'] ?? null;
        $oldStatus = $payload['old_status'] ?? null;

        if ($tournamentId) {
            // Specific tournament cache
            $tags[] = "tournament:{$tournamentId}";
            $tags[] = "public:tournament:{$tournamentId}";
        }

        // Status-specific cache invalidation
        if ($newStatus) {
            $tags[] = "public:tournaments:status:{$newStatus}";
        }

        if ($oldStatus) {
            $tags[] = "public:tournaments:status:{$oldStatus}";
        }

        // Featured tournaments (affected by status changes)
        $tags[] = 'public:tournaments:featured';
        $tags[] = 'tournaments:featured';

        // Upcoming tournaments (affected by status changes)
        $tags[] = 'public:tournaments:upcoming';
        $tags[] = 'tournaments:upcoming';

        // List cache (tournament might move between lists)
        $tags[] = 'public:tournaments:list';
        $tags[] = 'tournaments:list';

        // General tournament tags
        $tags[] = 'tournaments';

        return array_unique($tags);
    }

    /**
     * Get cache tags for tournament.settings.updated event
     *
     * @param array $payload
     * @return array
     */
    protected function getTournamentSettingsUpdatedTags(array $payload): array
    {
        $tags = ['public-api'];

        $tournamentId = $payload['tournament_id'] ?? $payload['id'] ?? null;

        if ($tournamentId) {
            // Specific tournament cache (settings are part of tournament data)
            $tags[] = "tournament:{$tournamentId}";
            $tags[] = "public:tournament:{$tournamentId}";
        }

        // Settings changes don't affect list views, only specific tournament
        // But invalidate general tournament tags for safety
        $tags[] = 'tournaments';

        return array_unique($tags);
    }

    /**
     * Get cache tags for tournament.deleted event
     *
     * @param array $payload
     * @return array
     */
    protected function getTournamentDeletedTags(array $payload): array
    {
        $tags = ['public-api'];

        $tournamentId = $payload['tournament_id'] ?? $payload['id'] ?? null;
        $status = $payload['status'] ?? null;

        if ($tournamentId) {
            // Specific tournament cache (must be removed)
            $tags[] = "tournament:{$tournamentId}";
            $tags[] = "public:tournament:{$tournamentId}";
        }

        // Aggressive invalidation - tournament removed from all lists
        $tags[] = 'public:tournaments:list';
        $tags[] = 'tournaments:list';
        $tags[] = 'public:tournaments:featured';
        $tags[] = 'tournaments:featured';
        $tags[] = 'public:tournaments:upcoming';
        $tags[] = 'tournaments:upcoming';

        // Status-specific cache (if tournament was in a specific status)
        if ($status) {
            $tags[] = "public:tournaments:status:{$status}";
        }

        // General tournament tags
        $tags[] = 'tournaments';

        return array_unique($tags);
    }

    /**
     * Get cache tags for sport.deleted event
     *
     * @param array $payload
     * @return array
     */
    protected function getSportDeletedTags(array $payload): array
    {
        $tags = ['public-api'];

        $sportId = $payload['sport_id'] ?? $payload['id'] ?? null;

        if ($sportId) {
            $tags[] = "sport:{$sportId}";
            $tags[] = "public:sport:{$sportId}";
        }

        // Invalidate sports list (sport removed)
        $tags[] = 'sports';
        $tags[] = 'sports:list';
        $tags[] = 'public:sports:list';

        // Also invalidate tournaments list (tournaments might reference this sport)
        $tags[] = 'public:tournaments:list';
        $tags[] = 'tournaments:list';

        return array_unique($tags);
    }

    /**
     * Get cache tags for venue.deleted event
     *
     * @param array $payload
     * @return array
     */
    protected function getVenueDeletedTags(array $payload): array
    {
        $tags = ['public-api'];

        $venueId = $payload['venue_id'] ?? $payload['id'] ?? null;

        if ($venueId) {
            $tags[] = "venue:{$venueId}";
            $tags[] = "public:venue:{$venueId}";
        }

        // Invalidate venues list (venue removed)
        $tags[] = 'venues';
        $tags[] = 'venues:list';
        $tags[] = 'public:venues:list';

        return array_unique($tags);
    }

}
