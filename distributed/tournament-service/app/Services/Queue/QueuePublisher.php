<?php

namespace App\Services\Queue;

use App\Jobs\QueueEventJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Redis;

class QueuePublisher
{
    /**
     * Service name (will be set from config)
     */
    protected string $serviceName;

    /**
     * Default queue connection
     */
    protected string $connection;

    /**
     * Default max retries for jobs
     */
    protected int $maxRetries;

    /**
     * Priority queue mappings
     */
    protected const PRIORITY_QUEUES = [
        'high' => 'high',
        'normal' => 'default',
        'low' => 'low',
    ];

    /**
     * Initialize the QueuePublisher
     */
    public function __construct()
    {
        $this->serviceName = config('app.name', 'unknown-service');
        $this->connection = config('queue.default', 'redis');
        $this->maxRetries = (int) config('queue.max_retries', 3);
    }

    /**
     * Dispatch an event to the queue
     *
     * @param string $queue Queue name
     * @param array $data Event payload data
     * @param string $eventType Event type (e.g., "match.completed")
     * @param string $priority Priority level: 'high', 'normal', or 'low'
     * @param int|null $delay Delay in seconds before processing
     * @return string|null Event ID if successful, null on failure
     */
    public function dispatch(
        string $queue,
        array $data,
        string $eventType = '',
        string $priority = 'normal',
        ?int $delay = null
    ): ?string {
        try {
            // Validate Redis connection before dispatching
            if (!$this->testRedisConnection()) {
                Log::error('Redis connection failed, cannot dispatch queue job', [
                    'queue' => $queue,
                    'event_type' => $eventType,
                    'service' => $this->serviceName,
                ]);
                return null;
            }

            // Generate unique event ID
            $eventId = (string) Str::uuid();

            // Build event structure
            $event = $this->buildEvent($eventId, $eventType, $data);

            // Determine target queue based on priority
            $targetQueue = $this->getPriorityQueue($queue, $priority);

            // Create the job (tries is set in constructor via event data)
            $job = new QueueEventJob($event);
            $job->onConnection($this->connection)->onQueue($targetQueue);

            // Dispatch with or without delay
            if ($delay !== null && $delay > 0) {
                $job->delay(Carbon::now()->addSeconds($delay));
            }

            // Dispatch the job
            dispatch($job);

            // Log the dispatch
            $this->logDispatch($eventId, $queue, $eventType, $priority, $delay, true);

            return $eventId;
        } catch (Exception $e) {
            $this->handleDispatchError($e, $queue, $eventType, $data);
            return null;
        }
    }

    /**
     * Dispatch a high priority event
     *
     * @param string $queue Queue name
     * @param array $data Event payload data
     * @param string $eventType Event type
     * @param int|null $delay Delay in seconds
     * @return string|null Event ID
     */
    public function dispatchHigh(
        string $queue,
        array $data,
        string $eventType = '',
        ?int $delay = null
    ): ?string {
        return $this->dispatch($queue, $data, $eventType, 'high', $delay);
    }

    /**
     * Dispatch a normal priority event
     *
     * @param string $queue Queue name
     * @param array $data Event payload data
     * @param string $eventType Event type
     * @param int|null $delay Delay in seconds
     * @return string|null Event ID
     */
    public function dispatchNormal(
        string $queue,
        array $data,
        string $eventType = '',
        ?int $delay = null
    ): ?string {
        return $this->dispatch($queue, $data, $eventType, 'normal', $delay);
    }

    /**
     * Dispatch a low priority event
     *
     * @param string $queue Queue name
     * @param array $data Event payload data
     * @param string $eventType Event type
     * @param int|null $delay Delay in seconds
     * @return string|null Event ID
     */
    public function dispatchLow(
        string $queue,
        array $data,
        string $eventType = '',
        ?int $delay = null
    ): ?string {
        return $this->dispatch($queue, $data, $eventType, 'low', $delay);
    }

    /**
     * Build the event structure according to specification
     *
     * @param string $eventId Unique event ID
     * @param string $eventType Event type
     * @param array $data Payload data
     * @return array Event structure
     */
    protected function buildEvent(string $eventId, string $eventType, array $data): array
    {
        return [
            'event_id' => $eventId,
            'event_type' => $eventType ?: $this->inferEventType($data),
            'service' => $this->serviceName,
            'payload' => $data,
            'timestamp' => Carbon::now()->utc()->format('Y-m-d\TH:i:s\Z'),
            'version' => '1.0',
            'retry_count' => 0,
            'max_retries' => $this->maxRetries,
        ];
    }

    /**
     * Infer event type from payload if not provided
     *
     * @param array $data Payload data
     * @return string Inferred event type
     */
    protected function inferEventType(array $data): string
    {
        // Try to infer from common payload keys
        if (isset($data['event_type'])) {
            return $data['event_type'];
        }

        if (isset($data['action'])) {
            $resource = $data['resource'] ?? $this->serviceName;
            return str_replace('-service', '', $resource) . '.' . $data['action'];
        }

        // Default fallback
        return 'event.unknown';
    }

    /**
     * Get the priority queue name
     *
     * @param string $baseQueue Base queue name
     * @param string $priority Priority level
     * @return string Queue name with priority
     */
    protected function getPriorityQueue(string $baseQueue, string $priority): string
    {
        $priority = strtolower($priority);

        if (!isset(self::PRIORITY_QUEUES[$priority])) {
            $priority = 'normal';
        }

        $prioritySuffix = self::PRIORITY_QUEUES[$priority];

        // If base queue is 'default', use priority directly
        if ($baseQueue === 'default') {
            return $prioritySuffix;
        }

        // Otherwise, append priority: "events-high", "events-normal", etc.
        return "{$baseQueue}-{$prioritySuffix}";
    }

    /**
     * Test Redis connection
     *
     * @return bool True if connection is available
     */
    protected function testRedisConnection(): bool
    {
        try {
            Redis::connection()->ping();
            return true;
        } catch (Exception $e) {
            Log::warning('Redis connection test failed', [
                'error' => $e->getMessage(),
                'service' => $this->serviceName,
            ]);
            return false;
        }
    }

    /**
     * Log successful dispatch
     *
     * @param string $eventId Event ID
     * @param string $queue Queue name
     * @param string $eventType Event type
     * @param string $priority Priority
     * @param int|null $delay Delay in seconds
     * @param bool $success Success status
     * @return void
     */
    protected function logDispatch(
        string $eventId,
        string $queue,
        string $eventType,
        string $priority,
        ?int $delay,
        bool $success
    ): void {
        $logData = [
            'event_id' => $eventId,
            'queue' => $queue,
            'event_type' => $eventType,
            'priority' => $priority,
            'service' => $this->serviceName,
            'connection' => $this->connection,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];

        if ($delay !== null && $delay > 0) {
            $logData['delay_seconds'] = $delay;
            $logData['scheduled_for'] = Carbon::now()->addSeconds($delay)->toIso8601String();
        }

        if ($success) {
            Log::info('Queue job dispatched successfully', $logData);
        } else {
            Log::error('Queue job dispatch failed', $logData);
        }
    }

    /**
     * Handle dispatch errors
     *
     * @param Exception $e Exception that occurred
     * @param string $queue Queue name
     * @param string $eventType Event type
     * @param array $data Payload data
     * @return void
     */
    protected function handleDispatchError(
        Exception $e,
        string $queue,
        string $eventType,
        array $data
    ): void {
        Log::error('Failed to dispatch queue job', [
            'queue' => $queue,
            'event_type' => $eventType,
            'service' => $this->serviceName,
            'connection' => $this->connection,
            'error' => $e->getMessage(),
            'error_class' => get_class($e),
            'trace' => $e->getTraceAsString(),
            'payload_keys' => array_keys($data),
            'timestamp' => Carbon::now()->toIso8601String(),
        ]);

        // Optionally, you could implement a fallback mechanism here
        // For example, storing failed dispatches in a database for retry
    }

    /**
     * Get service name
     *
     * @return string Service name
     */
    public function getServiceName(): string
    {
        return $this->serviceName;
    }

    /**
     * Set service name (useful for testing or dynamic configuration)
     *
     * @param string $serviceName Service name
     * @return self
     */
    public function setServiceName(string $serviceName): self
    {
        $this->serviceName = $serviceName;
        return $this;
    }
}
