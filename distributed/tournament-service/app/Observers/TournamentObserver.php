<?php

namespace App\Observers;

use App\Models\Tournament;
use App\Services\Events\EventPublisher;
use App\Services\Events\EventPayloadBuilder;
use Illuminate\Support\Facades\Log;

/**
 * Tournament Model Observer
 * 
 * Automatically publishes events for tournament model changes
 */
class TournamentObserver
{
    protected EventPublisher $eventPublisher;

    public function __construct(EventPublisher $eventPublisher)
    {
        $this->eventPublisher = $eventPublisher;
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
                $payload = EventPayloadBuilder::tournamentCreated(
                    $tournament->load(['sport', 'settings']),
                    $user
                );

                $this->eventPublisher->publish('sports.tournament.created', $payload);
                
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
                
                $payload = EventPayloadBuilder::tournamentStatusChanged(
                    $tournament->load(['sport', 'settings']),
                    $oldStatus
                );

                $this->eventPublisher->publish('sports.tournament.status.changed', $payload);
                
                Log::info('Tournament status changed event published via observer', [
                    'tournament_id' => $tournament->id,
                    'old_status' => $oldStatus,
                    'new_status' => $tournament->status,
                    'event_source' => 'observer'
                ]);
            } else {
                // General update event
                $oldData = $tournament->getOriginal();
                $payload = EventPayloadBuilder::tournamentUpdated(
                    $tournament->load(['sport', 'settings']),
                    $oldData
                );

                $this->eventPublisher->publish('sports.tournament.updated', $payload);
                
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
        try {
            $user = $this->getAuthenticatedUser();
            
            if ($user) {
                $payload = EventPayloadBuilder::tournamentDeleted($tournament, $user);

                $this->eventPublisher->publish('sports.tournament.deleted', $payload);
                
                Log::info('Tournament deleted event published via observer', [
                    'tournament_id' => $tournament->id,
                    'event_source' => 'observer'
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to publish tournament deleted event via observer', [
                'tournament_id' => $tournament->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Tournament "restored" event.
     *
     * @param Tournament $tournament
     * @return void
     */
    public function restored(Tournament $tournament): void
    {
        try {
            $user = $this->getAuthenticatedUser();
            
            if ($user) {
                $payload = array_merge(
                    EventPayloadBuilder::tournamentCreated($tournament, $user),
                    ['restored' => true, 'restored_at' => now()->toISOString()]
                );

                $this->eventPublisher->publish('sports.tournament.restored', $payload);
                
                Log::info('Tournament restored event published via observer', [
                    'tournament_id' => $tournament->id,
                    'event_source' => 'observer'
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to publish tournament restored event via observer', [
                'tournament_id' => $tournament->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle the Tournament "force deleted" event.
     *
     * @param Tournament $tournament
     * @return void
     */
    public function forceDeleted(Tournament $tournament): void
    {
        try {
            $user = $this->getAuthenticatedUser();
            
            if ($user) {
                $payload = array_merge(
                    EventPayloadBuilder::tournamentDeleted($tournament, $user),
                    ['force_deleted' => true, 'force_deleted_at' => now()->toISOString()]
                );

                $this->eventPublisher->publish('sports.tournament.force.deleted', $payload);
                
                Log::info('Tournament force deleted event published via observer', [
                    'tournament_id' => $tournament->id,
                    'event_source' => 'observer'
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to publish tournament force deleted event via observer', [
                'tournament_id' => $tournament->id,
                'error' => $e->getMessage()
            ]);
        }
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
            $user = auth()->user();
            if ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ];
            }
        } catch (\Exception $e) {
            // Auth might not be available in observer context
        }

        return null;
    }
}
