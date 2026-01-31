<?php

namespace App\Handlers;

use App\Contracts\EventHandlerInterface;
use App\Services\Queue\BaseEventHandler;
use App\Services\StandingsCalculator;
use App\Services\Queue\QueuePublisher;
use App\Models\MatchResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Match Completed Event Handler
 * 
 * CRITICAL HANDLER - Processes match.completed events and triggers standings calculation
 */
class MatchCompletedHandler extends BaseEventHandler implements EventHandlerInterface
{
    /**
     * Standings calculator service
     */
    private StandingsCalculator $standingsCalculator;

    /**
     * Queue publisher for publishing events
     */
    private QueuePublisher $queuePublisher;

    /**
     * Initialize the handler
     */
    public function __construct(StandingsCalculator $standingsCalculator, QueuePublisher $queuePublisher)
    {
        parent::__construct();
        $this->standingsCalculator = $standingsCalculator;
        $this->queuePublisher = $queuePublisher;
    }

    /**
     * Get the event types this handler can handle
     *
     * @return array
     */
    public function getHandledEventTypes(): array
    {
        return ['match.completed'];
    }

    /**
     * Process the match.completed event
     *
     * @param array $event Event data structure
     * @return void
     */
    protected function processEvent(array $event): void
    {
        $eventId = $event['event_id'] ?? 'unknown';
        $payload = $event['payload'] ?? [];

        // Validate required payload fields
        $requiredFields = ['match_id', 'tournament_id', 'home_team_id', 'away_team_id', 'home_score', 'away_score', 'result'];
        foreach ($requiredFields as $field) {
            if (!isset($payload[$field])) {
                throw new \InvalidArgumentException("Missing required field in match.completed payload: {$field}");
            }
        }

        $this->infoLog('Processing match.completed event', $event, [
            'match_id' => $payload['match_id'],
            'tournament_id' => $payload['tournament_id'],
        ]);

        DB::beginTransaction();
        try {
            // 1. Store match result
            $matchResult = MatchResult::updateOrCreate(
                ['match_id' => $payload['match_id']],
                [
                    'tournament_id' => $payload['tournament_id'],
                    'home_team_id' => $payload['home_team_id'],
                    'away_team_id' => $payload['away_team_id'],
                    'home_score' => (int) $payload['home_score'],
                    'away_score' => (int) $payload['away_score'],
                    'completed_at' => isset($payload['completed_at']) 
                        ? \Carbon\Carbon::parse($payload['completed_at']) 
                        : now(),
                    'processed_at' => now(),
                ]
            );

            // Set result attribute if it exists in the model
            if (isset($payload['result'])) {
                $matchResult->result = $payload['result'];
            }

            $this->infoLog('Match result stored', $event, [
                'match_result_id' => $matchResult->id,
                'match_id' => $matchResult->match_id,
            ]);

            // 2. Update standings using StandingsCalculator
            $this->standingsCalculator->updateStandingsFromMatch($matchResult);

            $this->infoLog('Standings updated from match', $event, [
                'match_id' => $matchResult->match_id,
                'tournament_id' => $matchResult->tournament_id,
            ]);

            // 3. Mark event as processed (idempotency)
            $this->markAsProcessed($eventId, 'match.completed');

            DB::commit();

            // 4. Publish standings.updated event (high priority)
            $this->queuePublisher->dispatchHigh('events', [
                'tournament_id' => $payload['tournament_id'],
                'match_id' => $payload['match_id'],
                'updated_at' => now()->toIso8601String(),
            ], 'standings.updated');

            $this->infoLog('Match result processed successfully', $event, [
                'match_id' => $payload['match_id'],
                'tournament_id' => $payload['tournament_id'],
            ]);

        } catch (Exception $e) {
            DB::rollBack();
            
            $this->errorLog('Failed to process match result', $event, [
                'match_id' => $payload['match_id'] ?? 'unknown',
                'tournament_id' => $payload['tournament_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger job retry mechanism
            throw $e;
        }
    }
}
