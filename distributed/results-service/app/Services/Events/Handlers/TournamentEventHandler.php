<?php

namespace App\Services\Events\Handlers;

use App\Contracts\BaseEventHandler;
use App\Services\StandingsCalculator;
use App\Services\Events\EventPublisher;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Tournament Event Handler
 *
 * Handles tournament-related events for the Results Service
 * - Triggers full standings recalculation when tournament status changes
 * - Handles tournament completion events
 */
class TournamentEventHandler extends BaseEventHandler
{
    protected StandingsCalculator $standingsCalculator;
    protected EventPublisher $eventPublisher;

    public function __construct(
        StandingsCalculator $standingsCalculator,
        EventPublisher $eventPublisher
    ) {
        $this->standingsCalculator = $standingsCalculator;
        $this->eventPublisher = $eventPublisher;
    }

    /**
     * Get the event types this handler can handle
     *
     * @return array
     */
    public function getHandledEventTypes(): array
    {
        return [
            'sports.tournament.status.changed',
        ];
    }

    /**
     * Process the tournament event
     *
     * @param array $event
     * @return void
     */
    protected function processEvent(array $event): void
    {
        $eventType = $event['event_type'];
        $payload = $event['payload'];

        Log::info('Tournament event received', [
            'event_id' => $event['event_id'],
            'event_type' => $eventType,
            'tournament_id' => $payload['tournament_id'] ?? 'unknown',
            'status' => $payload['status'] ?? 'unknown'
        ]);

        if ($eventType === 'sports.tournament.status.changed') {
            $this->handleTournamentStatusChanged($payload);
        } else {
            Log::warning('Unknown tournament event type', [
                'event_type' => $eventType,
                'event_id' => $event['event_id']
            ]);
        }
    }

    /**
     * Handle tournament status changed event
     *
     * @param array $payload
     * @return void
     */
    protected function handleTournamentStatusChanged(array $payload): void
    {
        $tournamentId = $payload['tournament_id'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$tournamentId) {
            Log::warning('Tournament status changed event missing tournament_id', [
                'payload' => $payload
            ]);
            return;
        }

        Log::info('Tournament status changed', [
            'tournament_id' => $tournamentId,
            'status' => $status
        ]);

        // If tournament is marked as completed, trigger full recalculation
        if ($status === 'completed' || $status === 'finished') {
            $this->triggerFullRecalculation($tournamentId);
        }
    }


    /**
     * Trigger full standings recalculation for tournament
     *
     * @param int $tournamentId
     * @return void
     */
    protected function triggerFullRecalculation(int $tournamentId): void
    {
        try {
            Log::info('Starting full standings recalculation', [
                'tournament_id' => $tournamentId
            ]);

            // Check if auto-recalculation is enabled
            if (!config('events.results_service.auto_recalculate_standings', true)) {
                Log::info('Auto recalculation disabled, skipping', [
                    'tournament_id' => $tournamentId
                ]);
                return;
            }

            // Get max attempts from config
            $maxAttempts = config('events.results_service.max_standings_recalculation_attempts', 3);
            $attempt = 0;
            $lastException = null;

            while ($attempt < $maxAttempts) {
                $attempt++;

                try {
                    $this->standingsCalculator->recalculateForTournament($tournamentId);

                    Log::info('Standings recalculation completed successfully', [
                        'tournament_id' => $tournamentId,
                        'attempt' => $attempt
                    ]);

                    // Publish standings recalculated event
                    $this->publishStandingsRecalculated($tournamentId);

                    return; // Success

                } catch (Exception $e) {
                    $lastException = $e;

                    Log::error('Standings recalculation failed', [
                        'tournament_id' => $tournamentId,
                        'attempt' => $attempt,
                        'max_attempts' => $maxAttempts,
                        'error' => $e->getMessage()
                    ]);

                    if ($attempt < $maxAttempts) {
                        // Wait before retry (exponential backoff)
                        $delay = 2000 * pow(2, $attempt - 1); // 2s, 4s, 8s
                        usleep($delay * 1000);
                    }
                }
            }

            // All attempts failed
            Log::error('All recalculation attempts failed', [
                'tournament_id' => $tournamentId,
                'attempts' => $maxAttempts,
                'error' => $lastException ? $lastException->getMessage() : 'Unknown error'
            ]);

            // Alert if configured
            if (config('events.error_handling.alert_on_failures', false)) {
                Log::critical('ALERT: Standings recalculation failed after all attempts', [
                    'tournament_id' => $tournamentId,
                    'error' => $lastException ? $lastException->getMessage() : 'Unknown error'
                ]);
            }

        } catch (Exception $e) {
            Log::error('Unexpected error during standings recalculation', [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Publish standings updated event after recalculation
     *
     * @param int $tournamentId
     * @return void
     */
    protected function publishStandingsRecalculated(int $tournamentId): void
    {
        try {
            $this->eventPublisher->publish('sports.standings.updated', [
                'tournament_id' => $tournamentId,
                'recalculated_at' => now()->toISOString()
            ]);

            Log::info('Standings updated event published after recalculation', [
                'tournament_id' => $tournamentId
            ]);
        } catch (Exception $e) {
            Log::error('Failed to publish standings updated event', [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
