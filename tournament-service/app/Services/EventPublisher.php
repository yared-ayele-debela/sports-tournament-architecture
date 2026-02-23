<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class EventPublisher
{
    protected string $serviceName;
    protected string $redisChannel;

    public function __construct()
    {
        $this->serviceName = config('app.name', 'tournament-service');
        $this->redisChannel = config('services.events.channel', 'sports-events');
    }

    /**
     * Publish an event to Redis Pub/Sub.
     *
     * @param string $eventType
     * @param array $payload
     * @return bool
     */
    public function publish(string $eventType, array $payload): bool
    {
        try {
            $event = $this->formatEvent($eventType, $payload);
            
            // Publish to Redis channel
            $published = Redis::publish($this->redisChannel, json_encode($event));
            
            // Log the event
            $this->logEvent($eventType, $payload, $published);
            
            return $published > 0;
        } catch (\Exception $e) {
            Log::error('Failed to publish event', [
                'event_type' => $eventType,
                'payload' => $payload,
                'error' => $e->getMessage(),
                'service' => $this->serviceName
            ]);
            
            return false;
        }
    }

    /**
     * Publish tournament created event.
     *
     * @param array $tournament
     * @return bool
     */
    public function publishTournamentCreated(array $tournament): bool
    {
        return $this->publish('tournament.created', [
            'tournament' => $tournament,
            'action' => 'created',
            'timestamp' => Carbon::now()->toISOString()
        ]);
    }

    /**
     * Publish tournament updated event.
     *
     * @param array $tournament
     * @param array $oldData
     * @return bool
     */
    public function publishTournamentUpdated(array $tournament, array $oldData = []): bool
    {
        return $this->publish('tournament.updated', [
            'tournament' => $tournament,
            'old_data' => $oldData,
            'updated_fields' => $this->getUpdatedFields($tournament, $oldData),
            'action' => 'updated',
            'timestamp' => Carbon::now()->toISOString()
        ]);
    }

    /**
     * Publish tournament status changed event.
     *
     * @param array $tournament
     * @param string $oldStatus
     * @return bool
     */
    public function publishTournamentStatusChanged(array $tournament, string $oldStatus): bool
    {
        return $this->publish('tournament.status.changed', [
            'tournament' => $tournament,
            'old_status' => $oldStatus,
            'new_status' => $tournament['status'],
            'action' => 'status_changed',
            'timestamp' => Carbon::now()->toISOString()
        ]);
    }

    /**
     * Format event according to specification.
     *
     * @param string $eventType
     * @param array $payload
     * @return array
     */
    protected function formatEvent(string $eventType, array $payload): array
    {
        return [
            'type' => $eventType,
            'payload' => $payload,
            'timestamp' => Carbon::now()->toISOString(),
            'service' => $this->serviceName,
            'version' => '1.0',
            'id' => uniqid('event_', true)
        ];
    }

    /**
     * Log published events.
     *
     * @param string $eventType
     * @param array $payload
     * @param int $published
     * @return void
     */
    protected function logEvent(string $eventType, array $payload, int $published): void
    {
        $status = $published > 0 ? 'published' : 'failed';
        
        Log::info("Event {$status}", [
            'event_type' => $eventType,
            'service' => $this->serviceName,
            'channel' => $this->redisChannel,
            'subscribers_notified' => $published,
            'payload_keys' => array_keys($payload),
            'timestamp' => Carbon::now()->toISOString()
        ]);
    }

    /**
     * Get updated fields between old and new data.
     *
     * @param array $newData
     * @param array $oldData
     * @return array
     */
    private function getUpdatedFields(array $newData, array $oldData): array
    {
        $updated = [];
        
        foreach ($newData as $key => $value) {
            if (!array_key_exists($key, $oldData) || $oldData[$key] !== $value) {
                $updated[$key] = [
                    'old' => $oldData[$key] ?? null,
                    'new' => $value
                ];
            }
        }
        
        return $updated;
    }

    /**
     * Test Redis connection.
     *
     * @return bool
     */
    public function testConnection(): bool
    {
        try {
            Redis::ping();
            return true;
        } catch (\Exception $e) {
            Log::error('Redis connection test failed', [
                'error' => $e->getMessage(),
                'service' => $this->serviceName
            ]);
            return false;
        }
    }
}
