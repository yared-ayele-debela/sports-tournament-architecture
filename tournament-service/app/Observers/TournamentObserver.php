<?php

namespace App\Observers;

use App\Models\Tournament;
use App\Services\Queue\QueuePublisher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Tournament Model Observer
 *
 * Automatically publishes events for tournament model changes
 */
class TournamentObserver
{
    protected QueuePublisher $queuePublisher;

    public function __construct(QueuePublisher $queuePublisher)
    {
        $this->queuePublisher = $queuePublisher;
    }

    /**
     * Handle the Tournament "created" event.
     *
     * @param Tournament $tournament
     * @return void
     */
    public function created(Tournament $tournament): void
    {
        try {
            // Get the user who created this tournament
            $user = $this->getAuthenticatedUser();

            if ($user) {
                // Dispatch to queue (default priority)
                $this->queuePublisher->dispatchNormal('events', [
                    'tournament_id' => $tournament->id,
                    'name' => $tournament->name,
                    'sport_id' => $tournament->sport_id,
                    'location' => $tournament->location,
                    'start_date' => $tournament->start_date?->toIso8601String(),
                    'end_date' => $tournament->end_date?->toIso8601String(),
                    'status' => $tournament->status,
                    'created_by' => $user['id'] ?? null,
                    'created_at' => now()->toIso8601String(),
                ], 'tournament.created');

                Log::info('Tournament created event published via observer', [
                    'tournament_id' => $tournament->id,
                    'event_source' => 'observer'
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to publish tournament created event via observer', [
                'tournament_id' => $tournament->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Tournament "updated" event.
     *
     * @param Tournament $tournament
     * @return void
     */
    public function updated(Tournament $tournament): void
    {
        try {
            // Check if status changed specifically
            if ($tournament->wasChanged('status')) {
                $oldStatus = $tournament->getOriginal('status');

                // Dispatch to queue (high priority - critical)
                $this->queuePublisher->dispatchHigh('events', [
                    'tournament_id' => $tournament->id,
                    'old_status' => $oldStatus,
                    'new_status' => $tournament->status,
                    'name' => $tournament->name,
                    'sport_id' => $tournament->sport_id,
                    'changed_at' => now()->toIso8601String(),
                ], 'tournament.status.changed');

                Log::info('Tournament status changed event published via observer', [
                    'tournament_id' => $tournament->id,
                    'old_status' => $oldStatus,
                    'new_status' => $tournament->status,
                    'event_source' => 'observer'
                ]);
            } else {
                // General update event
                $oldData = $tournament->getOriginal();

                // Dispatch to queue (default priority)
                $this->queuePublisher->dispatchNormal('events', [
                    'tournament_id' => $tournament->id,
                    'name' => $tournament->name,
                    'sport_id' => $tournament->sport_id,
                    'location' => $tournament->location,
                    'start_date' => $tournament->start_date?->toIso8601String(),
                    'end_date' => $tournament->end_date?->toIso8601String(),
                    'status' => $tournament->status,
                    'old_data' => $oldData,
                    'updated_fields' => array_keys($tournament->getChanges()),
                    'updated_at' => now()->toIso8601String(),
                ], 'tournament.updated');

                Log::info('Tournament updated event published via observer', [
                    'tournament_id' => $tournament->id,
                    'changed_fields' => array_keys($tournament->getChanges()),
                    'event_source' => 'observer'
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to publish tournament updated event via observer', [
                'tournament_id' => $tournament->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Tournament "deleted" event.
     *
     * @param Tournament $tournament
     * @return void
     */
    public function deleted(Tournament $tournament): void
    {
        // Tournament deletion events can be handled via queue if needed
        // Currently not implemented as tournament-service only publishes creation/update events
    }

    /**
     * Handle the Tournament "restored" event.
     *
     * @param Tournament $tournament
     * @return void
     */
    public function restored(Tournament $tournament): void
    {
        // Tournament restoration events can be handled via queue if needed
        // Currently not implemented as tournament-service only publishes creation/update events
    }

    /**
     * Handle the Tournament "force deleted" event.
     *
     * @param Tournament $tournament
     * @return void
     */
    public function forceDeleted(Tournament $tournament): void
    {
        // Tournament force deletion events can be handled via queue if needed
        // Currently not implemented as tournament-service only publishes creation/update events
    }

    /**
     * Get the currently authenticated user
     * In a real application, you might want to pass the user context differently
     * since observers don't have access to the request
     *
     * @return array|null
     */
    protected function getAuthenticatedUser(): ?array
    {
        // This is a placeholder - in a real application, you might:
        // 1. Use a middleware to store the current user in a service container
        // 2. Use Laravel's auth() facade if it's available
        // 3. Pass user context through a job queue system

        try {
            if (Auth::check()) {
                $user = Auth::user();
                if ($user) {
                    return [
                        'id' => $user->id ?? null,
                        'name' => $user->name ?? null,
                        'email' => $user->email ?? null
                    ];
                }
            }
        } catch (\Exception $e) {
            // Auth might not be available in observer context
            Log::debug('Could not get authenticated user in observer', [
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }
}
