<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class EventPublisher
{
    protected string $serviceName;
    protected string $channel;

    /**
     * Create a new EventPublisher instance.
     */
    public function __construct()
    {
        $this->serviceName = config('app.name', 'tournament-service');
        $this->channel = config('events.channel', 'sports-tournament-events');
    }

    /**
     * Publish an event to Redis Pub/Sub.
     *
     * @param string $eventType
     * @param array $payload
     * @param array $metadata Additional metadata (optional)
     * @return bool True if published successfully
     */
    public function publish(string $eventType, array $payload, array $metadata = []): bool
    {
        try {
            $event = [
                'type' => $eventType,
                'payload' => $payload,
                'timestamp' => Carbon::now()->toISOString(),
                'service' => $this->serviceName,
                'version' => '1.0',
                'id' => $this->generateEventId(),
                'metadata' => array_merge([
                    'environment' => config('app.env', 'local'),
                    'source' => $this->serviceName
                ], $metadata)
            ];

            // Serialize event to JSON
            $eventJson = json_encode($event);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to serialize event to JSON', [
                    'event_type' => $eventType,
                    'json_error' => json_last_error_msg(),
                    'payload' => $payload
                ]);
                return false;
            }

            // Publish to Redis channel
            $published = Redis::publish($this->channel, $eventJson);

            if ($published) {
                // Log successful event publication
                $this->logEvent($eventType, $event, 'published');
                
                // Also store event in Redis list for replay/debugging (optional)
                $this->storeEventHistory($event);
            } else {
                Log::error('Failed to publish event to Redis', [
                    'event_type' => $eventType,
                    'channel' => $this->channel,
                    'event' => $event
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Exception while publishing event', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $payload
            ]);
            return false;
        }
    }

    /**
     * Publish tournament created event.
     *
     * @param array $tournament
     * @param int $userId User who created the tournament
     * @return bool
     */
    public function publishTournamentCreated(array $tournament, int $userId): bool
    {
        return $this->publish('tournament.created', [
            'tournament' => $tournament,
            'created_by' => $userId
        ], [
            'action' => 'create',
            'resource_type' => 'tournament'
        ]);
    }

    /**
     * Publish tournament updated event.
     *
     * @param array $tournament
     * @param int $userId User who updated the tournament
     * @param array $changes Changes made (optional)
     * @return bool
     */
    public function publishTournamentUpdated(array $tournament, int $userId, array $changes = []): bool
    {
        return $this->publish('tournament.updated', [
            'tournament' => $tournament,
            'updated_by' => $userId,
            'changes' => $changes
        ], [
            'action' => 'update',
            'resource_type' => 'tournament',
            'change_count' => count($changes)
        ]);
    }

    /**
     * Publish tournament status changed event.
     *
     * @param array $tournament
     * @param string $oldStatus Previous status
     * @param string $newStatus New status
     * @param int $userId User who changed the status
     * @return bool
     */
    public function publishTournamentStatusChanged(array $tournament, string $oldStatus, string $newStatus, int $userId): bool
    {
        return $this->publish('tournament.status.changed', [
            'tournament' => $tournament,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $userId
        ], [
            'action' => 'status_change',
            'resource_type' => 'tournament',
            'status_transition' => "{$oldStatus}->{$newStatus}"
        ]);
    }

    /**
     * Publish sport created event.
     *
     * @param array $sport
     * @param int $userId User who created the sport
     * @return bool
     */
    public function publishSportCreated(array $sport, int $userId): bool
    {
        return $this->publish('sport.created', [
            'sport' => $sport,
            'created_by' => $userId
        ], [
            'action' => 'create',
            'resource_type' => 'sport'
        ]);
    }

    /**
     * Publish sport updated event.
     *
     * @param array $sport
     * @param int $userId User who updated the sport
     * @param array $changes Changes made (optional)
     * @return bool
     */
    public function publishSportUpdated(array $sport, int $userId, array $changes = []): bool
    {
        return $this->publish('sport.updated', [
            'sport' => $sport,
            'updated_by' => $userId,
            'changes' => $changes
        ], [
            'action' => 'update',
            'resource_type' => 'sport',
            'change_count' => count($changes)
        ]);
    }

    /**
     * Publish venue created event.
     *
     * @param array $venue
     * @param int $userId User who created the venue
     * @return bool
     */
    public function publishVenueCreated(array $venue, int $userId): bool
    {
        return $this->publish('venue.created', [
            'venue' => $venue,
            'created_by' => $userId
        ], [
            'action' => 'create',
            'resource_type' => 'venue'
        ]);
    }

    /**
     * Publish venue updated event.
     *
     * @param array $venue
     * @param int $userId User who updated the venue
     * @param array $changes Changes made (optional)
     * @return bool
     */
    public function publishVenueUpdated(array $venue, int $userId, array $changes = []): bool
    {
        return $this->publish('venue.updated', [
            'venue' => $venue,
            'updated_by' => $userId,
            'changes' => $changes
        ], [
            'action' => 'update',
            'resource_type' => 'venue',
            'change_count' => count($changes)
        ]);
    }

    /**
     * Generate unique event ID.
     */
    protected function generateEventId(): string
    {
        return uniqid($this->serviceName . '_', true);
    }

    /**
     * Log event publication.
     *
     * @param string $eventType
     * @param array $event
     * @param string $status
     */
    protected function logEvent(string $eventType, array $event, string $status): void
    {
        Log::info("Event {$status}", [
            'event_type' => $eventType,
            'event_id' => $event['id'],
            'service' => $event['service'],
            'timestamp' => $event['timestamp'],
            'channel' => $this->channel
        ]);
    }

    /**
     * Store event in Redis history for debugging/replay.
     *
     * @param array $event
     */
    protected function storeEventHistory(array $event): void
    {
        try {
            // Store in a Redis list with TTL (24 hours)
            $historyKey = $this->channel . ':history';
            Redis::lpush($historyKey, json_encode($event));
            
            // Keep only last 1000 events
            Redis::ltrim($historyKey, 0, 999);
            
            // Set TTL to 24 hours
            Redis::expire($historyKey, 86400);
        } catch (\Exception $e) {
            // Don't fail the main operation if history storage fails
            Log::warning('Failed to store event in history', [
                'error' => $e->getMessage(),
                'event_id' => $event['id'] ?? 'unknown'
            ]);
        }
    }

    /**
     * Get recent event history.
     *
     * @param int $limit Number of events to retrieve
     * @return array
     */
    public function getEventHistory(int $limit = 50): array
    {
        try {
            $historyKey = $this->channel . ':history';
            $events = Redis::lrange($historyKey, 0, $limit - 1);
            
            return array_map(function ($eventJson) {
                $event = json_decode($eventJson, true);
                return json_last_error() === JSON_ERROR_NONE ? $event : null;
            }, array_filter($events));
        } catch (\Exception $e) {
            Log::error('Failed to retrieve event history', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Test Redis connection.
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            return Redis::ping() === 'PONG';
        } catch (\Exception $e) {
            Log::error('Redis connection test failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
