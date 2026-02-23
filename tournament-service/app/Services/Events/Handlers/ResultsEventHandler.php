<?php

namespace App\Services\Events\Handlers;

use App\Contracts\BaseEventHandler;
use App\Models\Tournament;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Results Event Handler
 * 
 * Handles events from results-service:
 * - sports.standings.updated
 * - sports.statistics.updated
 */
class ResultsEventHandler extends BaseEventHandler
{
    /**
     * Get the event types this handler can handle
     *
     * @return array
     */
    public function getHandledEventTypes(): array
    {
        return [
            'sports.standings.updated',
            'sports.statistics.updated',
        ];
    }

    /**
     * Process the event
     *
     * @param array $event
     * @return void
     */
    protected function processEvent(array $event): void
    {
        $eventType = $event['event_type'];
        $payload = $event['payload'];

        switch ($eventType) {
            case 'sports.standings.updated':
                $this->handleStandingsUpdated($payload, $event['event_id']);
                break;

            case 'sports.statistics.updated':
                $this->handleStatisticsUpdated($payload, $event['event_id']);
                break;

            default:
                Log::warning('Unknown event type in ResultsEventHandler', [
                    'event_type' => $eventType,
                    'event_id' => $event['event_id']
                ]);
        }
    }

    /**
     * Handle standings updated event
     *
     * @param array $payload
     * @param string $eventId
     * @return void
     */
    protected function handleStandingsUpdated(array $payload, string $eventId): void
    {
        $tournamentId = $payload['tournament_id'] ?? null;
        
        if (!$tournamentId) {
            Log::warning('Standings updated event missing tournament_id', [
                'event_id' => $eventId,
                'payload' => $payload
            ]);
            return;
        }

        try {
            // Find the tournament
            $tournament = Tournament::find($tournamentId);
            
            if (!$tournament) {
                Log::warning('Tournament not found for standings update', [
                    'tournament_id' => $tournamentId,
                    'event_id' => $eventId
                ]);
                return;
            }

            // Update tournament cache with latest standings info
            $this->updateTournamentCache($tournament, $payload);

            // Log successful processing
            Log::info('Standings updated event processed', [
                'tournament_id' => $tournamentId,
                'tournament_name' => $tournament->name,
                'event_id' => $eventId,
                'match_id' => $payload['match_id'] ?? 'unknown',
                'home_score' => $payload['home_score'] ?? 'unknown',
                'away_score' => $payload['away_score'] ?? 'unknown'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to process standings updated event', [
                'tournament_id' => $tournamentId,
                'event_id' => $eventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Handle statistics updated event
     *
     * @param array $payload
     * @param string $eventId
     * @return void
     */
    protected function handleStatisticsUpdated(array $payload, string $eventId): void
    {
        $tournamentId = $payload['tournament_id'] ?? null;
        
        if (!$tournamentId) {
            Log::warning('Statistics updated event missing tournament_id', [
                'event_id' => $eventId,
                'payload' => $payload
            ]);
            return;
        }

        try {
            // Find the tournament
            $tournament = Tournament::find($tournamentId);
            
            if (!$tournament) {
                Log::warning('Tournament not found for statistics update', [
                    'tournament_id' => $tournamentId,
                    'event_id' => $eventId
                ]);
                return;
            }

            // Update tournament statistics cache
            $this->updateTournamentStatisticsCache($tournament, $payload);

            // Check if tournament should be marked as completed based on statistics
            $this->checkTournamentCompletion($tournament, $payload);

            // Log successful processing
            Log::info('Statistics updated event processed', [
                'tournament_id' => $tournamentId,
                'tournament_name' => $tournament->name,
                'event_id' => $eventId,
                'total_matches' => $payload['total_matches'] ?? 'unknown',
                'total_goals' => $payload['total_goals'] ?? 'unknown',
                'average_goals_per_match' => $payload['average_goals_per_match'] ?? 'unknown'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to process statistics updated event', [
                'tournament_id' => $tournamentId,
                'event_id' => $eventId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Update tournament cache with standings information
     *
     * @param Tournament $tournament
     * @param array $payload
     * @return void
     */
    protected function updateTournamentCache(Tournament $tournament, array $payload): void
    {
        $cacheKey = "tournament_standings:{$tournament->id}";
        
        $cacheData = [
            'tournament_id' => $tournament->id,
            'tournament_name' => $tournament->name,
            'last_updated' => now()->toISOString(),
            'last_match_id' => $payload['match_id'] ?? null,
            'last_score' => [
                'home_score' => $payload['home_score'] ?? null,
                'away_score' => $payload['away_score'] ?? null
            ]
        ];

        // Cache for 1 hour
        Cache::put($cacheKey, $cacheData, 3600);

        Log::debug('Tournament standings cache updated', [
            'tournament_id' => $tournament->id,
            'cache_key' => $cacheKey
        ]);
    }

    /**
     * Update tournament statistics cache
     *
     * @param Tournament $tournament
     * @param array $payload
     * @return void
     */
    protected function updateTournamentStatisticsCache(Tournament $tournament, array $payload): void
    {
        $cacheKey = "tournament_statistics:{$tournament->id}";
        
        $statistics = [
            'tournament_id' => $tournament->id,
            'total_matches' => $payload['total_matches'] ?? 0,
            'total_goals' => $payload['total_goals'] ?? 0,
            'average_goals_per_match' => $payload['average_goals_per_match'] ?? 0,
            'last_updated' => $payload['last_updated'] ?? now()->toISOString()
        ];

        // Cache for 2 hours
        Cache::put($cacheKey, $statistics, 7200);

        Log::debug('Tournament statistics cache updated', [
            'tournament_id' => $tournament->id,
            'cache_key' => $cacheKey,
            'total_matches' => $statistics['total_matches'],
            'total_goals' => $statistics['total_goals']
        ]);
    }

    /**
     * Check if tournament should be marked as completed
     *
     * @param Tournament $tournament
     * @param array $payload
     * @return void
     */
    protected function checkTournamentCompletion(Tournament $tournament, array $payload): void
    {
        // Only check completion if tournament is currently active
        if ($tournament->status !== 'active') {
            return;
        }

        $totalMatches = $payload['total_matches'] ?? 0;
        
        // You might want to implement logic to determine if all matches are completed
        // This could be based on tournament format, number of teams, etc.
        // For now, we'll just log the information
        
        Log::info('Tournament completion check', [
            'tournament_id' => $tournament->id,
            'current_status' => $tournament->status,
            'total_matches_played' => $totalMatches,
            'note' => 'Tournament completion logic should be implemented based on tournament format'
        ]);
    }
}
