<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class SubscribeToTournamentEvents implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event subscription.
     * This subscribes to Redis channels for tournament events.
     */
    public function handle(): void
    {
        try {
            // Subscribe to tournament status changes
            Redis::subscribe(['sports.tournament.status.changed'], function ($message) {
                $this->handleTournamentStatusChanged($message);
            });

            // Subscribe to tournament creation events
            Redis::subscribe(['sports.tournament.created'], function ($message) {
                $this->handleTournamentCreated($message);
            });

        } catch (\Exception $e) {
            Log::error('Failed to subscribe to tournament events', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle tournament status changed event
     */
    private function handleTournamentStatusChanged(string $message): void
    {
        try {
            $data = json_decode($message, true);
            
            Log::info('Tournament status changed received', [
                'tournament_id' => $data['tournament_id'] ?? null,
                'old_status' => $data['old_status'] ?? null,
                'new_status' => $data['new_status'] ?? null,
                'event_data' => $data,
            ]);

            // Cache tournament status for validation
            if (isset($data['tournament_id'], $data['new_status'])) {
                $cacheKey = "tournament_status_{$data['tournament_id']}";
                Redis::setex($cacheKey, 3600, $data['new_status']); // Cache for 1 hour
            }

        } catch (\Exception $e) {
            Log::error('Failed to handle tournament status changed', [
                'message' => $message,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle tournament created event
     */
    private function handleTournamentCreated(string $message): void
    {
        try {
            $data = json_decode($message, true);
            
            Log::info('Tournament created received', [
                'tournament_id' => $data['tournament_id'] ?? null,
                'tournament_name' => $data['name'] ?? null,
                'event_data' => $data,
            ]);

            // Cache tournament existence for validation
            if (isset($data['tournament_id'])) {
                $cacheKey = "tournament_exists_{$data['tournament_id']}";
                Redis::setex($cacheKey, 7200, json_encode($data)); // Cache for 2 hours
            }

        } catch (\Exception $e) {
            Log::error('Failed to handle tournament created', [
                'message' => $message,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
