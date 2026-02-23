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
        $this->serviceName = config('app.name', 'unknown-service');
    }

    public function subscribe(array $channels, callable $handler): void
    {
        $this->channels = $channels;
        $this->handler = $handler;

        Log::info('Starting event subscriber', [
            'service' => $this->serviceName,
            'channels' => $channels
        ]);

        $this->running = true;
        $this->listenToChannels();
    }

    protected function listenToChannels(): void
    {
        if (empty($this->channels) || $this->handler === null) {
            throw new Exception('No channels or handler configured');
        }

        Redis::subscribe($this->channels, function ($message, $channel) {
            try {
                $event = json_decode($message, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::warning('Invalid JSON received', [
                        'channel' => $channel,
                        'json_error' => json_last_error_msg()
                    ]);
                    return;
                }

                Log::info('Event received', [
                    'channel' => $channel,
                    'event_id' => $event['event_id'] ?? 'unknown',
                    'event_type' => $event['event_type'] ?? 'unknown'
                ]);

                call_user_func($this->handler, $event, $channel);

            } catch (\Exception $e) {
                Log::error('Error processing message', [
                    'channel' => $channel,
                    'error' => $e->getMessage()
                ]);
            }
        });
    }

    public function stop(): void
    {
        $this->running = false;
        Log::info('Event subscriber stopped', ['service' => $this->serviceName]);
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
