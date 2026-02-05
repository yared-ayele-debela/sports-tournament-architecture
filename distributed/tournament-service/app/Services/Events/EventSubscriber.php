<?php

namespace App\Services\Events;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Exception;
use Closure;

/**
 * Base Event Subscriber for Redis Pub/Sub
 *
 * Used by ALL services to subscribe to Redis channels and handle events
 */
class EventSubscriber
{
    protected string $serviceName;
    protected array $channels = [];
    protected ?Closure $handler = null;
    protected bool $running = false;
    protected int $reconnectDelay = 5000; // milliseconds
    protected int $maxReconnectAttempts = 10;
    protected array $signalHandlers = [];

    public function __construct()
    {
        $this->serviceName = config('app.name', 'unknown-service');
        $this->setupSignalHandlers();
    }

    /**
     * Subscribe to multiple Redis channels
     *
     * @param array $channels
     * @param callable $handler
     * @return void
     */
    public function subscribe(array $channels, callable $handler): void
    {
        $this->channels = $channels;
        $this->handler = $handler;

        Log::info('Starting event subscriber', [
            'service' => $this->serviceName,
            'channels' => $channels,
            'channel_count' => count($channels)
        ]);

        $this->running = true;
        $this->startListening();
    }

    /**
     * Start listening for events
     *
     * @return void
     */
    protected function startListening(): void
    {
        $reconnectAttempts = 0;

        while ($this->running && $reconnectAttempts < $this->maxReconnectAttempts) {
            try {
                $this->listenToChannels();
                $reconnectAttempts = 0; // Reset on successful connection
            } catch (Exception $e) {
                $reconnectAttempts++;

                Log::error('Event subscriber connection failed', [
                    'service' => $this->serviceName,
                    'channels' => $this->channels,
                    'attempt' => $reconnectAttempts,
                    'max_attempts' => $this->maxReconnectAttempts,
                    'error' => $e->getMessage()
                ]);

                if ($reconnectAttempts >= $this->maxReconnectAttempts) {
                    Log::error('Max reconnection attempts reached, stopping subscriber', [
                        'service' => $this->serviceName,
                        'attempts' => $reconnectAttempts
                    ]);
                    break;
                }

                // Wait before reconnecting
                $this->sleep($this->reconnectDelay);
            }
        }
    }

    /**
     * Listen to Redis channels
     *
     * @return void
     */
    protected function listenToChannels(): void
    {
        if (empty($this->channels) || $this->handler === null) {
            throw new Exception('No channels or handler configured');
        }

        Log::info('Connecting to Redis channels', [
            'service' => $this->serviceName,
            'channels' => $this->channels
        ]);

        // Create Redis pub/sub context
        $pubsub = Redis::connection()->pubSubLoop();

        // Subscribe to channels
        foreach ($this->channels as $channel) {
            $pubsub->subscribe($channel);
        }

        Log::info('Subscribed to Redis channels', [
            'service' => $this->serviceName,
            'channels' => $this->channels
        ]);

        // Listen for messages
        foreach ($pubsub as $message) {
            if (!$this->running) {
                break;
            }

            try {
                $this->handleMessage($message);
            } catch (Exception $e) {
                Log::error('Error handling message', [
                    'service' => $this->serviceName,
                    'channel' => $message->channel ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Unsubscribe and close connection
        foreach ($this->channels as $channel) {
            $pubsub->unsubscribe($channel);
        }

        unset($pubsub);
    }

    /**
     * Handle incoming message
     *
     * @param object $message
     * @return void
     */
    protected function handleMessage(object $message): void
    {
        // Only handle message events (not subscribe/unsubscribe notifications)
        if ($message->kind !== 'message') {
            return;
        }

        $channel = $message->channel;
        $payload = $message->payload;

        Log::debug('Received message', [
            'service' => $this->serviceName,
            'channel' => $channel,
            'payload_size' => strlen($payload)
        ]);

        // Parse and validate event
        $event = $this->parseEvent($payload);
        if ($event === null) {
            Log::warning('Failed to parse event', [
                'service' => $this->serviceName,
                'channel' => $channel,
                'payload' => substr($payload, 0, 200) // Log first 200 chars
            ]);
            return;
        }

        // Validate event structure
        if (!$this->validateEvent($event)) {
            Log::warning('Invalid event structure', [
                'service' => $this->serviceName,
                'channel' => $channel,
                'event_id' => $event['event_id'] ?? 'unknown',
                'event_type' => $event['event_type'] ?? 'unknown'
            ]);
            return;
        }

        Log::info('Processing event', [
            'service' => $this->serviceName,
            'channel' => $channel,
            'event_id' => $event['event_id'],
            'event_type' => $event['event_type'],
            'source_service' => $event['service'] ?? 'unknown'
        ]);

        // Call handler
        call_user_func($this->handler, $event, $channel);
    }

    /**
     * Parse event JSON
     *
     * @param string $payload
     * @return array|null
     */
    protected function parseEvent(string $payload): ?array
    {
        $event = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('JSON decode failed', [
                'service' => $this->serviceName,
                'error' => json_last_error_msg(),
                'payload' => substr($payload, 0, 200)
            ]);
            return null;
        }

        return $event;
    }

    /**
     * Validate event structure
     *
     * @param array $event
     * @return bool
     */
    protected function validateEvent(array $event): bool
    {
        $requiredFields = ['event_id', 'event_type', 'service', 'payload', 'timestamp', 'version'];

        foreach ($requiredFields as $field) {
            if (!isset($event[$field])) {
                return false;
            }
        }

        // Validate UUID format for event_id
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $event['event_id'])) {
            return false;
        }

        // Validate timestamp format
        if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $event['timestamp'])) {
            return false;
        }

        return true;
    }

    /**
     * Setup signal handlers for graceful shutdown
     *
     * @return void
     */
    protected function setupSignalHandlers(): void
    {
        if (function_exists('pcntl_signal')) {
            pcntl_async_signals(true);

            $this->signalHandlers = [
                SIGTERM => [$this, 'handleShutdown'],
                SIGINT => [$this, 'handleShutdown'],
                SIGHUP => [$this, 'handleReload']
            ];

            foreach ($this->signalHandlers as $signal => $handler) {
                pcntl_signal($signal, $handler);
            }
        }
    }

    /**
     * Handle shutdown signals
     *
     * @param int $signal
     * @return void
     */
    public function handleShutdown(int $signal): void
    {
        Log::info('Received shutdown signal', [
            'service' => $this->serviceName,
            'signal' => $signal,
            'signal_name' => $this->getSignalName($signal)
        ]);

        $this->stop();
    }

    /**
     * Handle reload signals
     *
     * @param int $signal
     * @return void
     */
    public function handleReload(int $signal): void
    {
        Log::info('Received reload signal', [
            'service' => $this->serviceName,
            'signal' => $signal,
            'signal_name' => $this->getSignalName($signal)
        ]);

        // Could implement config reload logic here
    }

    /**
     * Get signal name for logging
     *
     * @param int $signal
     * @return string
     */
    protected function getSignalName(int $signal): string
    {
        $signals = [
            SIGTERM => 'SIGTERM',
            SIGINT => 'SIGINT',
            SIGHUP => 'SIGHUP'
        ];

        return $signals[$signal] ?? 'UNKNOWN';
    }

    /**
     * Stop the subscriber
     *
     * @return void
     */
    public function stop(): void
    {
        Log::info('Stopping event subscriber', [
            'service' => $this->serviceName,
            'channels' => $this->channels
        ]);

        $this->running = false;
    }

    /**
     * Check if subscriber is running
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        return $this->running;
    }

    /**
     * Get subscribed channels
     *
     * @return array
     */
    public function getChannels(): array
    {
        return $this->channels;
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
     * Set reconnection configuration
     *
     * @param int $delayMs
     * @param int $maxAttempts
     * @return self
     */
    public function setReconnectConfig(int $delayMs, int $maxAttempts): self
    {
        $this->reconnectDelay = $delayMs;
        $this->maxReconnectAttempts = $maxAttempts;
        return $this;
    }

    /**
     * Sleep for specified milliseconds
     *
     * @param int $milliseconds
     * @return void
     */
    protected function sleep(int $milliseconds): void
    {
        usleep($milliseconds * 1000);
    }

    /**
     * Destructor - ensure clean shutdown
     */
    public function __destruct()
    {
        $this->stop();
    }
}
