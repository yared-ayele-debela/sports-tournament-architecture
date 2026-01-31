<?php

namespace App\Handlers;

use App\Contracts\EventHandlerInterface;
use App\Services\Queue\BaseEventHandler;
use App\Services\StandingsCalculator;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Tournament Status Changed Event Handler
 * 
 * Handles tournament.status.changed events:
 * - If tournament marked as completed, trigger full recalculation
 * - Recalculate all standings for that tournament
 */
class TournamentStatusChangedHandler extends BaseEventHandler implements EventHandlerInterface
{
    /**
     * Standings calculator service
     */
    private StandingsCalculator $standingsCalculator;

    /**
     * Initialize the handler
     */
    public function __construct(StandingsCalculator $standingsCalculator)
    {
        parent::__construct();
        $this->standingsCalculator = $standingsCalculator;
    }

    /**
     * Get the event types this handler can handle
     *
     * @return array
     */
    public function getHandledEventTypes(): array
    {
        return ['tournament.status.changed'];
    }

    /**
     * Process the tournament.status.changed event
     *
     * @param array $event Event data structure
     * @return void
     */
    protected function processEvent(array $event): void
    {
        $payload = $event['payload'] ?? [];
        $tournamentId = $this->getPayloadData($event, 'tournament_id');
        $oldStatus = $this->getPayloadData($event, 'old_status');
        $newStatus = $this->getPayloadData($event, 'new_status');

        if (!$tournamentId || !$newStatus) {
            $this->warningLog('Invalid tournament.status.changed payload - missing required fields', $event);
            return;
        }

        $this->infoLog('Processing tournament.status.changed event', $event, [
            'tournament_id' => $tournamentId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);

        // If tournament marked as completed, trigger full recalculation
        if ($newStatus === 'completed') {
            try {
                $this->infoLog('Tournament completed - triggering full standings recalculation', $event, [
                    'tournament_id' => $tournamentId,
                ]);

                // Recalculate all standings for the tournament
                $this->standingsCalculator->recalculateForTournament($tournamentId);

                $this->infoLog('Tournament standings recalculated successfully', $event, [
                    'tournament_id' => $tournamentId,
                ]);

            } catch (Exception $e) {
                $this->errorLog('Failed to recalculate tournament standings', $event, [
                    'tournament_id' => $tournamentId,
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e),
                ]);

                // Re-throw to trigger job retry mechanism
                throw $e;
            }
        } else {
            $this->debugLog('Tournament status changed but not completed - no recalculation needed', $event, [
                'tournament_id' => $tournamentId,
                'new_status' => $newStatus,
            ]);
        }
    }
}
