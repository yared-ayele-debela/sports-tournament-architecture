<?php

namespace App\Services\Events;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

/**
 * Base Event Publisher for Redis Pub/Sub
 *
 * Used by Results Service to publish events to Redis channels
 */
class EventPublisher
{
    protected string $serviceName;
    protected string $version = '1.0';
    protected int $retryAttempts = 3;
    protected int $retryDelay = 100; // milliseconds

    public function __construct()
    {
        $this->serviceName = config('app.name', 'results-service');
    }

    /**
     * Publish event to Redis channel
     *
     * @param string $eventType The event type (e.g., 'sports.match.completed')
     * @param array $data
     * @return bool
     */
    public function publish(string $eventType, array $data): bool
    {
        $defaultChannel = config('events.default_channel', 'sports.events');
        $event = $this->createEvent($eventType, $data);

        try {
            $this->publishWithRetry($defaultChannel, $event);

            Log::info('Event published successfully', [
                'channel' => $defaultChannel,
                'event_id' => $event['event_id'],
                'event_type' => $event['event_type'],
                'service' => $this->serviceName,
                'payload_size' => strlen(json_encode($event))
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to publish event', [
                'channel' => $defaultChannel,
                'event_id' => $event['event_id'],
                'event_type' => $event['event_type'],
                'service' => $this->serviceName,
                'error' => $e->getMessage(),
                'attempts' => $this->retryAttempts
            ]);

            return false;
        }
    }

    /**
     * Create standardized event structure
     *
     * @param string $eventType The event type (e.g., 'sports.match.completed')
     * @param array $data
     * @return array
     */
    protected function createEvent(string $eventType, array $data): array
    {
        return [
            'event_id' => $this->generateEventId(),
            'event_type' => $eventType,
            'service' => $this->serviceName,
            'payload' => $data,
            'timestamp' => now()->utc()->toISOString(),
            'version' => $this->version
        ];
    }

    /**
     * Generate unique event ID
     *
     * @return string
     */
    protected function generateEventId(): string
    {
        return Str::uuid()->toString();
    }


    /**
     * Publish event with retry logic
     *
     * @param string $channel
     * @param array $event
     * @throws Exception
     */
    protected function publishWithRetry(string $channel, array $event): void
    {
        $payload = json_encode($event);

        if ($payload === false) {
            throw new Exception('Failed to encode event payload to JSON');
        }

        $lastException = null;

        for ($attempt = 1; $attempt <= $this->retryAttempts; $attempt++) {
            try {
                // Use events connection like match-service does
                $published = app('redis')->connection('events')->publish($channel, $payload);

                if ($published === 0) {
                    Log::warning('No subscribers received event', [
                        'channel' => $channel,
                        'event_id' => $event['event_id'],
                        'attempt' => $attempt
                    ]);
                }

                // Success - exit retry loop
                return;

            } catch (Exception $e) {
                $lastException = $e;

                Log::warning('Event publish attempt failed', [
                    'channel' => $channel,
                    'event_id' => $event['event_id'],
                    'attempt' => $attempt,
                    'max_attempts' => $this->retryAttempts,
                    'error' => $e->getMessage()
                ]);

                // Don't wait on the last attempt
                if ($attempt < $this->retryAttempts) {
                    usleep($this->retryDelay * 1000); // Convert to microseconds
                }
            }
        }

        // All attempts failed
        throw $lastException ?: new Exception('Event publishing failed after all retry attempts');
    }

    /**
     * Publish multiple events in batch
     *
     * @param array $events Array of ['event_type' => string, 'data' => array]
     * @return array Results with success/failure for each event
     */
    public function publishBatch(array $events): array
    {
        $results = [];

        foreach ($events as $index => $eventData) {
            if (!isset($eventData['event_type']) || !isset($eventData['data'])) {
                $results[$index] = [
                    'success' => false,
                    'error' => 'Missing event_type or data in event specification'
                ];
                continue;
            }

            $results[$index] = [
                'success' => $this->publish($eventData['event_type'], $eventData['data']),
                'event_type' => $eventData['event_type']
            ];
        }

        return $results;
    }

    /**
     * Check Redis connection health
     *
     * @return bool
     */
    public function isHealthy(): bool
    {
        try {
            app('redis')->connection('events')->ping();
            return true;
        } catch (Exception $e) {
            Log::error('Redis health check failed', [
                'service' => $this->serviceName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
