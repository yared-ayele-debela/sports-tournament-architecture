<?php

namespace App\Services\Events\Handlers;

use App\Contracts\BaseEventHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Monitoring Event Handler
 * 
 * Handles all events for logging, monitoring, and metrics tracking
 */
class MonitoringEventHandler extends BaseEventHandler
{
    /**
     * Event metrics cache key
     */
    const EVENT_METRICS_KEY = 'gateway:event_metrics';
    
    /**
     * Event metrics TTL (1 hour)
     */
    const METRICS_TTL = 3600;

    /**
     * Get the event types this handler can handle
     *
     * @return array
     */
    public function getHandledEventTypes(): array
    {
        return [
            // Tournament events
            'sports.tournament.created',
            'sports.tournament.updated',
            'sports.tournament.deleted',
            'sports.tournament.started',
            'sports.tournament.completed',
            'sports.tournament.status.changed',
            
            // Match events
            'sports.match.created',
            'sports.match.updated',
            'sports.match.deleted',
            'sports.match.started',
            'sports.match.completed',
            'sports.match.status.changed',
            'sports.match.event.recorded',
            
            // Team events
            'sports.team.created',
            'sports.team.updated',
            'sports.team.deleted',
            'sports.player.created',
            'sports.player.updated',
            'sports.player.deleted',
            
            // Results events
            'sports.standings.updated',
            'sports.statistics.updated',
            'sports.tournament.recalculated',
            'sports.standings.recalculated',
            
            // Sport and Venue events
            'sports.sport.created',
            'sports.sport.updated',
            'sports.sport.deleted',
            'sports.venue.created',
            'sports.venue.updated',
            'sports.venue.deleted',
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
        
        // Log to separate monitoring log file
        $this->logToMonitoringFile($event);
        
        // Update event metrics
        $this->updateEventMetrics($event);
        
        // Track event latency
        $this->trackEventLatency($event);
        
        // Log specific event details
        $this->logEventDetails($eventType, $payload);
        
        $this->infoLog('Event monitoring completed', $event, [
            'metrics_updated' => true,
            'monitoring_logged' => true
        ]);
    }

    /**
     * Log event to separate monitoring log file
     *
     * @param array $event
     * @return void
     */
    protected function logToMonitoringFile(array $event): void
    {
        $logData = [
            'timestamp' => now()->toISOString(),
            'event_id' => $event['event_id'],
            'event_type' => $event['event_type'],
            'source_service' => $event['service'],
            'event_timestamp' => $event['timestamp'],
            'version' => $event['version'],
            'payload_size' => strlen(json_encode($event['payload'])),
            'gateway_processed_at' => now()->toISOString(),
        ];

        // Log to monitoring channel (configured in logging.php)
        Log::channel('monitoring')->info('Event processed', $logData);
    }

    /**
     * Update event metrics
     *
     * @param array $event
     * @return void
     */
    protected function updateEventMetrics(array $event): void
    {
        $metrics = Cache::get(self::EVENT_METRICS_KEY, [
            'total_events' => 0,
            'events_by_type' => [],
            'events_by_service' => [],
            'last_updated' => now()->toISOString()
        ]);

        // Increment total events
        $metrics['total_events']++;

        // Increment by event type
        $eventType = $event['event_type'];
        if (!isset($metrics['events_by_type'][$eventType])) {
            $metrics['events_by_type'][$eventType] = 0;
        }
        $metrics['events_by_type'][$eventType]++;

        // Increment by source service
        $sourceService = $event['service'];
        if (!isset($metrics['events_by_service'][$sourceService])) {
            $metrics['events_by_service'][$sourceService] = 0;
        }
        $metrics['events_by_service'][$sourceService]++;

        // Update timestamp
        $metrics['last_updated'] = now()->toISOString();

        // Store in cache
        Cache::put(self::EVENT_METRICS_KEY, $metrics, self::METRICS_TTL);
    }

    /**
     * Track event latency
     *
     * @param array $event
     * @return void
     */
    protected function trackEventLatency(array $event): void
    {
        try {
            $eventTimestamp = Carbon::parse($event['timestamp']);
            $processedAt = now();
            $latencyMs = $eventTimestamp->diffInMilliseconds($processedAt);

            // Log high latency events
            if ($latencyMs > 5000) { // 5 seconds
                Log::warning('High event latency detected', [
                    'event_id' => $event['event_id'],
                    'event_type' => $event['event_type'],
                    'latency_ms' => $latencyMs,
                    'event_timestamp' => $event['timestamp'],
                    'processed_at' => $processedAt->toISOString()
                ]);
            }

            // Store latency metrics
            $latencyKey = 'gateway:event_latency:' . $event['event_type'];
            $latencies = Cache::get($latencyKey, []);
            $latencies[] = $latencyMs;
            
            // Keep only last 100 measurements
            if (count($latencies) > 100) {
                $latencies = array_slice($latencies, -100);
            }
            
            Cache::put($latencyKey, $latencies, self::METRICS_TTL);

        } catch (\Exception $e) {
            Log::error('Error tracking event latency', [
                'event_id' => $event['event_id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Log specific event details
     *
     * @param string $eventType
     * @param array $payload
     * @return void
     */
    protected function logEventDetails(string $eventType, array $payload): void
    {
        switch ($eventType) {
            case 'sports.tournament.status.changed':
                Log::info('Tournament status change detected', [
                    'tournament_id' => $payload['tournament_id'] ?? 'unknown',
                    'old_status' => $payload['old_status'] ?? 'unknown',
                    'new_status' => $payload['new_status'] ?? 'unknown',
                    'transition_reason' => $payload['transition_reason'] ?? 'unknown'
                ]);
                break;

            case 'sports.match.completed':
                Log::info('Match completion detected', [
                    'match_id' => $payload['match_id'] ?? 'unknown',
                    'tournament_id' => $payload['tournament_id'] ?? 'unknown',
                    'home_team_id' => $payload['home_team_id'] ?? 'unknown',
                    'away_team_id' => $payload['away_team_id'] ?? 'unknown',
                    'home_score' => $payload['home_score'] ?? 'unknown',
                    'away_score' => $payload['away_score'] ?? 'unknown'
                ]);
                break;

            case 'sports.standings.updated':
                Log::info('Standings update detected', [
                    'tournament_id' => $payload['tournament_id'] ?? 'unknown',
                    'match_id' => $payload['match_id'] ?? 'unknown',
                    'trigger' => $payload['trigger'] ?? 'unknown'
                ]);
                break;

            case 'sports.team.updated':
                Log::info('Team update detected', [
                    'team_id' => $payload['team_id'] ?? 'unknown',
                    'changes' => $this->getChangedFields($payload)
                ]);
                break;

            case 'sports.player.updated':
                Log::info('Player update detected', [
                    'player_id' => $payload['player_id'] ?? 'unknown',
                    'team_id' => $payload['team_id'] ?? 'unknown',
                    'changes' => $this->getChangedFields($payload)
                ]);
                break;
        }
    }

    /**
     * Get changed fields from payload
     *
     * @param array $payload
     * @return array
     */
    protected function getChangedFields(array $payload): array
    {
        $changes = [];
        
        if (isset($payload['old_data']) && isset($payload['new_data'])) {
            $oldData = $payload['old_data'];
            $newData = $payload['new_data'];
            
            foreach ($newData as $key => $value) {
                if (isset($oldData[$key]) && $oldData[$key] !== $value) {
                    $changes[] = $key;
                }
            }
        }
        
        return $changes;
    }

    /**
     * Get current event metrics
     *
     * @return array
     */
    public function getEventMetrics(): array
    {
        return Cache::get(self::EVENT_METRICS_KEY, [
            'total_events' => 0,
            'events_by_type' => [],
            'events_by_service' => [],
            'last_updated' => now()->toISOString()
        ]);
    }

    /**
     * Get latency metrics for event type
     *
     * @param string $eventType
     * @return array
     */
    public function getLatencyMetrics(string $eventType): array
    {
        $latencies = Cache::get('gateway:event_latency:' . $eventType, []);
        
        if (empty($latencies)) {
            return [
                'count' => 0,
                'avg_ms' => 0,
                'min_ms' => 0,
                'max_ms' => 0
            ];
        }

        return [
            'count' => count($latencies),
            'avg_ms' => round(array_sum($latencies) / count($latencies), 2),
            'min_ms' => min($latencies),
            'max_ms' => max($latencies)
        ];
    }
}
