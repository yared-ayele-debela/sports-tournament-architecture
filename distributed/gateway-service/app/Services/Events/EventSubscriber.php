<?php

namespace App\Services\Events;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Exception;

class EventSubscriber
{
    protected string $serviceName;
    protected array $channels = [];
    protected $handler = null;
    protected bool $running = false;

    public function __construct()
    {
        $this->serviceName = config('app.name', 'gateway-service');
    }

    public function subscribe(array $channels, callable $handler): void
    {
        $this->channels = $channels;
        $this->handler = $handler;

        Log::info('Starting Gateway Service event subscriber', [
            'service' => $this->serviceName,
            'channels' => $this->channels
        ]);

        $this->running = true;
        $this->listenWithReconnection();
    }

    /**
     * Listen with reconnection logic
     *
     * @return void
     */
    protected function listenWithReconnection(): void
    {
        while ($this->running) {
            try {
                // Use Laravel's Redis facade for events connection
                $redis = Redis::connection('events');

                Log::info('Gateway Service Redis connection established, subscribing to channels', [
                    'service' => $this->serviceName,
                    'channels' => $this->channels
                ]);

                // Subscribe using Laravel's Redis facade with error handling
                $redis->subscribe($this->channels, function ($message, $channel) {
                    if (!$this->running) {
                        Log::info('Stopping message processing - service not running', [
                            'service' => $this->serviceName,
                            'channel' => $channel
                        ]);
                        return;
                    }

                    try {
                        $event = json_decode($message, true);
                        
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            Log::warning('Invalid JSON received in Gateway Service', [
                                'service' => $this->serviceName,
                                'channel' => $channel,
                                'json_error' => json_last_error_msg()
                            ]);
                            return;
                        }

                        Log::info('Event received in Gateway Service', [
                            'service' => $this->serviceName,
                            'channel' => $channel,
                            'event_id' => $event['event_id'] ?? 'unknown',
                            'event_type' => $event['event_type'] ?? 'unknown'
                        ]);

                        // Call the handler
                        call_user_func($this->handler, $event, $channel);

                    } catch (\Exception $e) {
                        Log::error('Error processing message in Gateway Service', [
                            'service' => $this->serviceName,
                            'channel' => $channel,
                            'error' => $e->getMessage()
                        ]);
                    }
                });

                // If we get here, the subscription ended gracefully
                Log::info('Gateway Service Redis subscription ended gracefully', [
                    'service' => $this->serviceName
                ]);
                break;

            } catch (\Exception $e) {
                Log::error('Gateway Service Redis subscription failed, will retry in 5 seconds', [
                    'service' => $this->serviceName,
                    'error' => $e->getMessage()
                ]);

                if ($this->running) {
                    // Wait 5 seconds before reconnecting
                    sleep(5);
                } else {
                    Log::info('Gateway Service stopped, will not reconnect', [
                        'service' => $this->serviceName
                    ]);
                    break;
                }
            }
        }
    }

    public function stop(): void
    {
        $this->running = false;
        Log::info('Gateway Service event subscriber stopped', ['service' => $this->serviceName]);
    }

    public function isRunning(): bool
    {
        return $this->running;
    }

    public function setServiceName(string $serviceName): void
    {
        $this->serviceName = $serviceName;
    }
}
