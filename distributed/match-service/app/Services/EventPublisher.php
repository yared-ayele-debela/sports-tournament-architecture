<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class EventPublisher
{
    protected const CHANNELS = [
        'MATCH_CREATED' => 'match.created',
        'MATCH_EVENT_RECORDED' => 'match.event.recorded',
        'MATCH_COMPLETED' => 'match.completed',
        'MATCH_UPDATED' => 'match.updated',
        'MATCH_CANCELLED' => 'match.cancelled',
    ];

    public function publishMatchCreated(array $matchData): void
    {
        $event = [
            'event_type' => 'match.created',
            'match_id' => $matchData['id'],
            'tournament_id' => $matchData['tournament_id'],
            'home_team_id' => $matchData['home_team_id'],
            'away_team_id' => $matchData['away_team_id'],
            'venue_id' => $matchData['venue_id'],
            'referee_id' => $matchData['referee_id'],
            'match_date' => $matchData['match_date'],
            'round_number' => $matchData['round_number'],
            'status' => $matchData['status'],
            'timestamp' => now()->toISOString(),
        ];

        $this->publish(self::CHANNELS['MATCH_CREATED'], $event);
    }

    public function publishMatchEventRecorded(array $eventData, array $matchData): void
    {
        $event = [
            'event_type' => 'match.event.recorded',
            'event_id' => $eventData['id'],
            'match_id' => $eventData['match_id'],
            'team_id' => $eventData['team_id'],
            'player_id' => $eventData['player_id'],
            'event_type' => $eventData['event_type'],
            'minute' => $eventData['minute'],
            'description' => $eventData['description'],
            'home_score' => $matchData['home_score'],
            'away_score' => $matchData['away_score'],
            'current_minute' => $matchData['current_minute'],
            'timestamp' => now()->toISOString(),
        ];

        $this->publish(self::CHANNELS['MATCH_EVENT_RECORDED'], $event);
    }

    public function publishMatchCompleted(array $matchData, array $reportData): void
    {
        $event = [
            'event_type' => 'match.completed',
            'match_id' => $matchData['id'],
            'tournament_id' => $matchData['tournament_id'],
            'home_team_id' => $matchData['home_team_id'],
            'away_team_id' => $matchData['away_team_id'],
            'home_score' => $matchData['home_score'],
            'away_score' => $matchData['away_score'],
            'status' => $matchData['status'],
            'report_id' => $reportData['id'],
            'referee' => $reportData['referee'],
            'attendance' => $reportData['attendance'],
            'summary' => $reportData['summary'],
            'timestamp' => now()->toISOString(),
        ];

        $this->publish(self::CHANNELS['MATCH_COMPLETED'], $event);
    }

    public function publishMatchUpdated(array $matchData): void
    {
        $event = [
            'event_type' => 'match.updated',
            'match_id' => $matchData['id'],
            'tournament_id' => $matchData['tournament_id'],
            'home_team_id' => $matchData['home_team_id'],
            'away_team_id' => $matchData['away_team_id'],
            'status' => $matchData['status'],
            'home_score' => $matchData['home_score'] ?? null,
            'away_score' => $matchData['away_score'] ?? null,
            'current_minute' => $matchData['current_minute'] ?? null,
            'timestamp' => now()->toISOString(),
        ];

        $this->publish(self::CHANNELS['MATCH_UPDATED'], $event);
    }

    public function publishMatchCancelled(array $matchData): void
    {
        $event = [
            'event_type' => 'match.cancelled',
            'match_id' => $matchData['id'],
            'tournament_id' => $matchData['tournament_id'],
            'home_team_id' => $matchData['home_team_id'],
            'away_team_id' => $matchData['away_team_id'],
            'reason' => $matchData['cancellation_reason'] ?? 'Not specified',
            'timestamp' => now()->toISOString(),
        ];

        $this->publish(self::CHANNELS['MATCH_CANCELLED'], $event);
    }

    protected function publish(string $channel, array $data): void
    {
        try {
            Redis::publish($channel, json_encode($data, JSON_THROW_ON_ERROR));
        } catch (\Exception $e) {
            // Log error but don't break the application flow
            logger()->error('Failed to publish Redis event', [
                'channel' => $channel,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }

    public function getChannels(): array
    {
        return self::CHANNELS;
    }
}
