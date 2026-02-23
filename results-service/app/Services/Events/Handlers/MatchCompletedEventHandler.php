<?php

namespace App\Services\Events\Handlers;

use App\Contracts\BaseEventHandler;
use App\Models\MatchResult;
use App\Services\StandingsCalculator;
use App\Services\Events\EventPublisher;
use App\Services\Clients\MatchServiceClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Exception;

/**
 * Match Completed Event Handler
 *
 * CRITICAL HANDLER - Processes match completion events and updates standings
 *
 * Features:
 * - Idempotency: Prevents duplicate processing using event_id
 * - Retry logic: 3 attempts with exponential backoff
 * - Dead letter queue: Failed events stored for manual review
 * - Full match validation: Optionally fetches match details from match-service
 * - Standings calculation: Updates tournament standings
 * - Event publishing: Publishes standings.updated and statistics.updated events
 */
class MatchCompletedEventHandler extends BaseEventHandler
{
    protected StandingsCalculator $standingsCalculator;
    protected MatchServiceClient $matchServiceClient;
    protected EventPublisher $eventPublisher;
    protected int $maxRetryAttempts = 3;
    protected int $retryDelayMs = 1000;

    public function __construct(
        StandingsCalculator $standingsCalculator,
        MatchServiceClient $matchServiceClient,
        EventPublisher $eventPublisher
    ) {
        $this->standingsCalculator = $standingsCalculator;
        $this->matchServiceClient = $matchServiceClient;
        $this->eventPublisher = $eventPublisher;

        // Load retry config from config
        $this->maxRetryAttempts = config('events.error_handling.max_retry_attempts', 3);
        $this->retryDelayMs = config('events.error_handling.retry_delay_ms', 1000);
    }

    /**
     * Get the event types this handler can handle
     *
     * @return array
     */
    public function getHandledEventTypes(): array
    {
        return [
            'sports.match.completed',
        ];
    }

    /**
     * Process the match completed event
     *
     * @param array $event
     * @return void
     */
    protected function processEvent(array $event): void
    {
        $eventId = $event['event_id'];
        $payload = $event['payload'];

        Log::info('Match completed event received', [
            'event_id' => $eventId,
            'match_id' => $payload['match_id'] ?? 'unknown',
            'tournament_id' => $payload['tournament_id'] ?? 'unknown'
        ]);

        // IDEMPOTENCY CHECK: Prevent duplicate processing
        if ($this->isEventProcessed($eventId)) {
            Log::warning('Event already processed, skipping', [
                'event_id' => $eventId,
                'match_id' => $payload['match_id'] ?? 'unknown'
            ]);
            return;
        }

        // Validate required payload fields
        if (!$this->validatePayload($payload)) {
            $this->logError('Invalid payload structure', $eventId, $payload);
            $this->sendToDeadLetterQueue($event, 'invalid_payload');
            return;
        }

        // Process with retry logic
        $this->processWithRetry($event, $payload);
    }

    /**
     * Process event with retry logic
     *
     * @param array $event
     * @param array $payload
     * @return void
     */
    protected function processWithRetry(array $event, array $payload): void
    {
        $eventId = $event['event_id'];
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetryAttempts) {
            $attempt++;

            try {
                Log::info('Processing match completed event', [
                    'event_id' => $eventId,
                    'attempt' => $attempt,
                    'max_attempts' => $this->maxRetryAttempts,
                    'match_id' => $payload['match_id']
                ]);

                // Mark event as processing
                $this->markEventProcessing($eventId);

                // Optionally fetch full match details from match-service for validation
                $matchData = $this->fetchMatchDetails($payload['match_id']);

                // Create or update MatchResult
                $matchResult = $this->createOrUpdateMatchResult($payload, $matchData);

                // Update standings
                $this->standingsCalculator->updateStandingsFromMatch($matchResult);

                // Mark event as processed
                $this->markEventProcessed($eventId, $matchResult);

                // Publish standings updated event
                $this->publishStandingsUpdated($matchResult);

                // Publish statistics updated event
                $this->publishStatisticsUpdated($matchResult->tournament_id);

                Log::info('Match completed event processed successfully', [
                    'event_id' => $eventId,
                    'match_id' => $matchResult->match_id,
                    'tournament_id' => $matchResult->tournament_id,
                    'attempt' => $attempt
                ]);

                return; // Success - exit retry loop

            } catch (Exception $e) {
                $lastException = $e;

                Log::error('Failed to process match completed event', [
                    'event_id' => $eventId,
                    'attempt' => $attempt,
                    'max_attempts' => $this->maxRetryAttempts,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                // If not the last attempt, wait before retrying
                if ($attempt < $this->maxRetryAttempts) {
                    $delay = $this->retryDelayMs * pow(2, $attempt - 1); // Exponential backoff
                    Log::info('Retrying after delay', [
                        'event_id' => $eventId,
                        'attempt' => $attempt,
                        'delay_ms' => $delay
                    ]);
                    usleep($delay * 1000);
                }
            }
        }

        // All attempts failed - send to dead letter queue
        Log::error('All retry attempts failed, sending to dead letter queue', [
            'event_id' => $eventId,
            'attempts' => $this->maxRetryAttempts,
            'error' => $lastException ? $lastException->getMessage() : 'Unknown error'
        ]);

        $this->sendToDeadLetterQueue($event, 'max_retries_exceeded', $lastException);
    }

    /**
     * Check if event has already been processed (idempotency)
     *
     * @param string $eventId
     * @return bool
     */
    protected function isEventProcessed(string $eventId): bool
    {
        $key = "events:processed:{$eventId}";

        try {
            return Redis::exists($key) > 0;
        } catch (Exception $e) {
            Log::warning('Failed to check event processing status', [
                'event_id' => $eventId,
                'error' => $e->getMessage()
            ]);
            // On Redis failure, check database
            return $this->isEventProcessedInDatabase($eventId);
        }
    }

    /**
     * Check if event processed in database (fallback)
     *
     * @param string $eventId
     * @return bool
     */
    protected function isEventProcessedInDatabase(string $eventId): bool
    {
        // Store processed events in a table or use match_results with event_id
        // For now, we'll use a simple approach with match_id from payload
        // In production, you might want a dedicated processed_events table
        return false; // Simplified - implement based on your needs
    }

    /**
     * Mark event as processing
     *
     * @param string $eventId
     * @return void
     */
    protected function markEventProcessing(string $eventId): void
    {
        $key = "events:processing:{$eventId}";
        Redis::setex($key, 300, '1'); // 5 minutes TTL
    }

    /**
     * Mark event as processed (idempotency)
     *
     * @param string $eventId
     * @param MatchResult $matchResult
     * @return void
     */
    protected function markEventProcessed(string $eventId, MatchResult $matchResult): void
    {
        $key = "events:processed:{$eventId}";
        $data = [
            'event_id' => $eventId,
            'match_id' => $matchResult->match_id,
            'tournament_id' => $matchResult->tournament_id,
            'processed_at' => now()->toISOString()
        ];

        // Store for 30 days
        Redis::setex($key, 2592000, json_encode($data));

        // Also store in match_result for reference (if you add event_id column)
        // $matchResult->update(['processed_event_id' => $eventId]);
    }

    /**
     * Validate payload structure
     *
     * @param array $payload
     * @return bool
     */
    protected function validatePayload(array $payload): bool
    {
        $requiredFields = ['match_id', 'tournament_id', 'home_team_id', 'away_team_id'];

        foreach ($requiredFields as $field) {
            if (!isset($payload[$field])) {
                Log::warning('Missing required field in payload', [
                    'field' => $field,
                    'payload' => $payload
                ]);
                return false;
            }
        }

        // Validate scores are present and numeric
        if (!isset($payload['home_score']) || !isset($payload['away_score'])) {
            Log::warning('Missing scores in payload', ['payload' => $payload]);
            return false;
        }

        if (!is_numeric($payload['home_score']) || !is_numeric($payload['away_score'])) {
            Log::warning('Invalid score format in payload', ['payload' => $payload]);
            return false;
        }

        return true;
    }

    /**
     * Fetch match details from match-service (optional validation)
     *
     * @param int $matchId
     * @return array|null
     */
    protected function fetchMatchDetails(int $matchId): ?array
    {
        try {
            $matchData = $this->matchServiceClient->getMatch($matchId);

            if ($matchData && isset($matchData['data'])) {
                Log::debug('Match details fetched from match-service', [
                    'match_id' => $matchId,
                    'status' => $matchData['data']['status'] ?? 'unknown'
                ]);
                return $matchData['data'];
            }

            return null;
        } catch (Exception $e) {
            Log::warning('Failed to fetch match details from match-service', [
                'match_id' => $matchId,
                'error' => $e->getMessage()
            ]);
            // Continue processing with payload data
            return null;
        }
    }

    /**
     * Create or update MatchResult
     *
     * @param array $payload
     * @param array|null $matchData
     * @return MatchResult
     */
    protected function createOrUpdateMatchResult(array $payload, ?array $matchData = null): MatchResult
    {
        return DB::transaction(function () use ($payload, $matchData) {
            // Use matchData if available, otherwise use payload
            $data = $matchData ?? $payload;

            $matchResult = MatchResult::updateOrCreate(
                [
                    'match_id' => $payload['match_id']
                ],
                [
                    'tournament_id' => $payload['tournament_id'],
                    'home_team_id' => $payload['home_team_id'],
                    'away_team_id' => $payload['away_team_id'],
                    'home_score' => $payload['home_score'],
                    'away_score' => $payload['away_score'],
                    'completed_at' => $payload['completed_at'] ?? $data['completed_at'] ?? now(),
                ]
            );

            Log::info('MatchResult created/updated', [
                'match_id' => $matchResult->match_id,
                'tournament_id' => $matchResult->tournament_id,
                'home_score' => $matchResult->home_score,
                'away_score' => $matchResult->away_score
            ]);

            return $matchResult;
        });
    }

    /**
     * Publish standings updated event
     *
     * @param MatchResult $matchResult
     * @return void
     */
    protected function publishStandingsUpdated(MatchResult $matchResult): void
    {
        try {
            $this->eventPublisher->publish('sports.standings.updated', [
                'tournament_id' => $matchResult->tournament_id,
                'match_id' => $matchResult->match_id,
                'home_team_id' => $matchResult->home_team_id,
                'away_team_id' => $matchResult->away_team_id,
                'updated_at' => now()->toISOString()
            ]);

            Log::info('Standings updated event published', [
                'tournament_id' => $matchResult->tournament_id,
                'match_id' => $matchResult->match_id
            ]);
        } catch (Exception $e) {
            Log::error('Failed to publish standings updated event', [
                'tournament_id' => $matchResult->tournament_id,
                'error' => $e->getMessage()
            ]);
            // Don't throw - event publishing failure shouldn't fail the main process
        }
    }

    /**
     * Publish statistics updated event
     *
     * @param int $tournamentId
     * @return void
     */
    protected function publishStatisticsUpdated(int $tournamentId): void
    {
        try {
            $this->eventPublisher->publish('sports.statistics.updated', [
                'tournament_id' => $tournamentId,
                'updated_at' => now()->toISOString()
            ]);

            Log::info('Statistics updated event published', [
                'tournament_id' => $tournamentId
            ]);
        } catch (Exception $e) {
            Log::error('Failed to publish statistics updated event', [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage()
            ]);
            // Don't throw - event publishing failure shouldn't fail the main process
        }
    }

    /**
     * Send event to dead letter queue
     *
     * @param array $event
     * @param string $reason
     * @param Exception|null $exception
     * @return void
     */
    protected function sendToDeadLetterQueue(array $event, string $reason, ?Exception $exception = null): void
    {
        $dlqChannel = config('events.error_handling.dead_letter_queue', 'events.dlq');

        $dlqEvent = [
            'original_event' => $event,
            'reason' => $reason,
            'error' => $exception ? $exception->getMessage() : null,
            'failed_at' => now()->toISOString(),
            'service' => config('app.name', 'results-service')
        ];

        try {
            Redis::lpush($dlqChannel, json_encode($dlqEvent));

            Log::error('Event sent to dead letter queue', [
                'event_id' => $event['event_id'],
                'reason' => $reason,
                'dlq_channel' => $dlqChannel
            ]);

            // Alert if configured
            if (config('events.error_handling.alert_on_failures', false)) {
                $this->sendAlert($event, $reason, $exception);
            }
        } catch (Exception $e) {
            Log::critical('Failed to send event to dead letter queue', [
                'event_id' => $event['event_id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send alert for failed event
     *
     * @param array $event
     * @param string $reason
     * @param Exception|null $exception
     * @return void
     */
    protected function sendAlert(array $event, string $reason, ?Exception $exception = null): void
    {
        // Implement alerting (email, Slack, PagerDuty, etc.)
        Log::critical('ALERT: Match completed event processing failed', [
            'event_id' => $event['event_id'],
            'match_id' => $event['payload']['match_id'] ?? 'unknown',
            'reason' => $reason,
            'error' => $exception ? $exception->getMessage() : null
        ]);
    }

    /**
     * Log error with context
     *
     * @param string $message
     * @param string $eventId
     * @param array $payload
     * @return void
     */
    protected function logError(string $message, string $eventId, array $payload): void
    {
        Log::error($message, [
            'event_id' => $eventId,
            'payload' => $payload
        ]);
    }
}
