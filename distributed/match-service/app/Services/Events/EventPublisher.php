<?php

namespace App\Services\Events;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

/**
 * Base Event Publisher for Redis Pub/Sub
 * 
 * Used by ALL services to publish events to Redis channels
 */
class EventPublisher
{
    protected string $serviceName;
    protected string $version = '1.0';
    protected int $retryAttempts = 3;
    protected int $retryDelay = 100; // milliseconds

    public function __construct()
    {
        $this->serviceName = config('app.name', 'unknown-service');
    }

    /**
     * Publish event to Redis channel
     *
     * @param string $channel
     * @param array $data
     * @return bool
     */
    public function publish(string $channel, array $data): bool
    {
        $event = $this->createEvent($channel, $data);
        
        try {
            $this->publishWithRetry($channel, $event);
            
            Log::info('Event published successfully', [
                'channel' => $channel,
                'event_id' => $event['event_id'],
                'event_type' => $event['event_type'],
                'service' => $this->serviceName,
                'payload_size' => strlen(json_encode($event))
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to publish event', [
                'channel' => $channel,
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
     * @param string $channel
     * @param array $data
     * @return array
     */
    protected function createEvent(string $channel, array $data): array
    {
        return [
            'event_id' => $this->generateEventId(),
            'event_type' => $this->extractEventType($channel),
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
     * Extract event type from channel
     * Returns the full channel name as event type for consistency
     *
     * @param string $channel
     * @return string
     */
    protected function extractEventType(string $channel): string
    {
        return $channel;
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
     * @param array $events Array of ['channel' => string, 'data' => array]
     * @return array Results with success/failure for each event
     */
    public function publishBatch(array $events): array
    {
        $results = [];
        
        foreach ($events as $index => $eventData) {
            if (!isset($eventData['channel']) || !isset($eventData['data'])) {
                $results[$index] = [
                    'success' => false,
                    'error' => 'Missing channel or data in event specification'
                ];
                continue;
            }

            $results[$index] = [
                'success' => $this->publish($eventData['channel'], $eventData['data']),
                'channel' => $eventData['channel']
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

    /**
     * Get service name
     *
     * @return string
     */
    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    /**
     * Set service name (useful for testing)
     *
     * @param string $serviceName
     * @return self
     */
    public function setServiceName(string $serviceName): self
    {
        $this->serviceName = $serviceName;
        return $this;
    }

    /**
     * Set event version
     *
     * @param string $version
     * @return self
     */
    public function setVersion(string $version): self
    {
        $this->version = $version;
        return $this;
    }

    /**
     * Set retry configuration
     *
     * @param int $attempts
     * @param int $delayMs
     * @return self
     */
    public function setRetryConfig(int $attempts, int $delayMs): self
    {
        $this->retryAttempts = $attempts;
        $this->retryDelay = $delayMs;
        return $this;
    }
}
