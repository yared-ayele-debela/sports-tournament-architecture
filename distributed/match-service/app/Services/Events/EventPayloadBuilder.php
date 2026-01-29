<?php

namespace App\Services\Events;

use App\Models\Match;
use App\Models\MatchEvent;
use App\Models\MatchGame;
use App\Models\MatchReport;
use Carbon\Carbon;

/**
 * Event Payload Builder for Match Service
 * 
 * Helper class to build standardized event payloads
 */
class EventPayloadBuilder
{
    /**
     * Build match created event payload
     *
     * @param \App\Models\Match $match
     * @param array $user
     * @return array
     */
    public static function matchCreated(\App\Models\MatchGame $match, array $user): array
    {
        return [
            'match_id' => $match->id,
            'tournament_id' => $match->tournament_id,
            'home_team_id' => $match->home_team_id,
            'away_team_id' => $match->away_team_id,
            'venue_id' => $match->venue_id,
            'scheduled_at' => $match->match_date?->toISOString(),
            'status' => $match->status,
            'match_type' => $match->match_type ?? 'regular',
            'created_by' => $user['id'],
            'created_by_name' => $user['name'] ?? null,
            'created_at' => $match->created_at->toISOString(),
            'teams' => [
                'home' => [
                    'id' => $match->home_team_id,
                    'name' => $match->homeTeam?->name ?? null
                ],
                'away' => [
                    'id' => $match->away_team_id,
                    'name' => $match->awayTeam?->name ?? null
                ]
            ],
            'tournament' => [
                'id' => $match->tournament_id,
                'name' => $match->tournament?->name ?? null
            ],
            'venue' => [
                'id' => $match->venue_id,
                'name' => $match->venue?->name ?? null
            ]
        ];
    }

    /**
     * Build match updated event payload
     *
     * @param Match $match
     * @param array $oldData
     * @return array
     */
    public static function matchUpdated(MatchGame $match, array $oldData): array
    {
        $newData = $match->toArray();
        
        // Safely detect changes by comparing specific fields
        $updatedFields = [];
        $fieldsToCheck = ['match_date', 'venue_id', 'status', 'home_team_id', 'away_team_id', 'match_type'];
        
        foreach ($fieldsToCheck as $field) {
            if (isset($oldData[$field]) && isset($newData[$field]) && $oldData[$field] != $newData[$field]) {
                $updatedFields[] = $field;
            }
        }
        
        return [
            'match_id' => $match->id,
            'tournament_id' => $match->tournament_id,
            'home_team_id' => $match->home_team_id,
            'away_team_id' => $match->away_team_id,
            'updated_fields' => $updatedFields,
            'changes' => [
                'old' => array_intersect_key($oldData, array_flip($fieldsToCheck)),
                'new' => array_intersect_key($newData, array_flip($fieldsToCheck))
            ],
            'updated_at' => now()->toISOString(),
            'status' => $match->status,
            'scheduled_at' => $match->match_date?->toISOString()
        ];
    }

    /**
     * Build match status changed event payload
     *
     * @param Match $match
     * @param string $oldStatus
     * @return array
     */
    public static function matchStatusChanged(MatchGame $match, string $oldStatus): array
    {
        return [
            'match_id' => $match->id,
            'tournament_id' => $match->tournament_id,
            'home_team_id' => $match->home_team_id,
            'away_team_id' => $match->away_team_id,
            'old_status' => $oldStatus,
            'new_status' => $match->status,
            'status_changed_at' => now()->toISOString(),
            'scheduled_at' => $match->match_date?->toISOString(),
            'reason' => self::getStatusChangeReason($oldStatus, $match->status),
            'teams' => [
                'home' => [
                    'id' => $match->home_team_id,
                    'name' => $match->homeTeam?->name ?? null
                ],
                'away' => [
                    'id' => $match->away_team_id,
                    'name' => $match->awayTeam?->name ?? null
                ]
            ]
        ];
    }

    /**
     * Build match started event payload
     *
     * @param MatchGame $match
     * @param array $user
     * @return array
     */
    public static function matchStarted(MatchGame $match, array $user): array
    {
        return [
            'match_id' => $match->id,
            'tournament_id' => $match->tournament_id,
            'home_team_id' => $match->home_team_id,
            'away_team_id' => $match->away_team_id,
            'started_at' => now()->toISOString(),
            'scheduled_at' => $match->match_date?->toISOString(),
            'started_by' => $user['id'],
            'started_by_name' => $user['name'] ?? null,
            'venue_id' => $match->venue_id,
            'venue_name' => $match->venue?->name ?? null,
            'teams' => [
                'home' => [
                    'id' => $match->home_team_id,
                    'name' => $match->homeTeam?->name ?? null
                ],
                'away' => [
                    'id' => $match->away_team_id,
                    'name' => $match->awayTeam?->name ?? null
                ]
            ]
        ];
    }

    /**
     * Build match event recorded payload (real-time events)
     *
     * @param MatchEvent $matchEvent
     * @param array $user
     * @return array
     */
    public static function matchEventRecorded(MatchEvent $matchEvent, array $user): array
    {
        $match = $matchEvent->match;
        
        return [
            'event_id' => $matchEvent->id,
            'match_id' => $matchEvent->match_id,
            'tournament_id' => $match->tournament_id,
            'event_type' => $matchEvent->event_type,
            'minute' => $matchEvent->minute,
            'player_id' => $matchEvent->player_id,
            'player_name' => $matchEvent->player?->full_name ?? null,
            'team_id' => $matchEvent->team_id,
            'team_name' => $matchEvent->team?->name ?? null,
            'event_data' => $matchEvent->event_data ?? [],
            'recorded_by' => $user['id'],
            'recorded_by_name' => $user['name'] ?? null,
            'recorded_at' => $matchEvent->created_at->toISOString(),
            'match_context' => [
                'home_team_id' => $match->home_team_id,
                'away_team_id' => $match->away_team_id,
                'current_score' => [
                    'home' => $match->home_score ?? 0,
                    'away' => $match->away_score ?? 0
                ],
                'match_time' => $matchEvent->minute,
                'status' => $match->status
            ],
            'event_details' => self::formatEventDetails($matchEvent)
        ];
    }

    /**
     * Build match completed event payload (CRITICAL for standings)
     *
     * @param Match $match
     * @param MatchReport $report
     * @param array $user
     * @return array
     */
    public static function matchCompleted(MatchGame $match, MatchReport $report, array $user): array
    {
        // Refresh the match model to get the latest scores
        $match->refresh();
        
        return [
            'match_id' => $match->id,
            'tournament_id' => $match->tournament_id,
            'home_team_id' => $match->home_team_id,
            'away_team_id' => $match->away_team_id,
            'home_score' => (int) ($match->home_score ?? 0),
            'away_score' => (int) ($match->away_score ?? 0),
            'completed_at' => $report->updated_at->toISOString(),
            'duration_minutes' => 90, // Default duration
            'completed_by' => $user['id'],
            'completed_by_name' => $user['name'] ?? null,
            'result' => self::determineResult($match->home_score ?? 0, $match->away_score ?? 0),
            'match_report_id' => $report->id,
            'venue_id' => $match->venue_id,
            'venue_name' => $match->venue?->name ?? null,
            'teams' => [
                'home' => [
                    'id' => $match->home_team_id,
                    'name' => $match->homeTeam?->name ?? null,
                    'score' => (int) ($match->home_score ?? 0),
                    'result' => self::determineTeamResult($match->home_score ?? 0, $match->away_score ?? 0, 'home')
                ],
                'away' => [
                    'id' => $match->away_team_id,
                    'name' => $match->awayTeam?->name ?? null,
                    'score' => (int) ($match->away_score ?? 0),
                    'result' => self::determineTeamResult($match->home_score ?? 0, $match->away_score ?? 0, 'away')
                ]
            ],
            'statistics' => [
                'total_events' => $match->matchEvents->count(),
                'goals' => $match->matchEvents()->where('event_type', 'goal')->count(),
                'yellow_cards' => $match->matchEvents()->where('event_type', 'yellow_card')->count(),
                'red_cards' => $match->matchEvents()->where('event_type', 'red_card')->count(),
                'substitutions' => $match->matchEvents()->where('event_type', 'substitution')->count()
            ],
            'tournament_context' => [
                'id' => $match->tournament_id,
                'name' => $match->tournament?->name ?? null,
                'match_type' => $match->match_type ?? 'regular'
            ],
            'report_details' => [
                'summary' => $report->summary,
                'referee' => $report->referee,
                'attendance' => $report->attendance
            ]
        ];
    }

    /**
     * Build match deleted event payload
     *
     * @param Match $match
     * @param array $user
     * @return array
     */
    public static function matchDeleted(MatchGame $match, array $user): array
    {
        return [
            'match_id' => $match->id,
            'tournament_id' => $match->tournament_id,
            'home_team_id' => $match->home_team_id,
            'away_team_id' => $match->away_team_id,
            'deleted_by' => $user['id'],
            'deleted_by_name' => $user['name'] ?? null,
            'deleted_at' => now()->toISOString(),
            'original_data' => [
                'scheduled_at' => $match->match_date?->toISOString(),
                'venue_id' => $match->venue_id,
                'status' => $match->status,
                'match_type' => $match->match_type ?? 'regular'
            ],
            'teams' => [
                'home' => [
                    'id' => $match->home_team_id,
                    'name' => $match->homeTeam?->name ?? null
                ],
                'away' => [
                    'id' => $match->away_team_id,
                    'name' => $match->awayTeam?->name ?? null
                ]
            ]
        ];
    }


    /**
     * Format event details based on event type
     *
     * @param MatchEvent $matchEvent
     * @return array
     */
    protected static function formatEventDetails(MatchEvent $matchEvent): array
    {
        $baseDetails = [
            'event_type' => $matchEvent->event_type,
            'minute' => $matchEvent->minute,
            'player_id' => $matchEvent->player_id,
            'team_id' => $matchEvent->team_id
        ];

        switch ($matchEvent->event_type) {
            case 'goal':
                return array_merge($baseDetails, [
                    'goal_type' => $matchEvent->event_data['goal_type'] ?? 'regular',
                    'assist_player_id' => $matchEvent->event_data['assist_player_id'] ?? null,
                    'score_after' => [
                        'home' => $matchEvent->event_data['home_score'] ?? 0,
                        'away' => $matchEvent->event_data['away_score'] ?? 0
                    ]
                ]);

            case 'yellow_card':
            case 'red_card':
                return array_merge($baseDetails, [
                    'card_reason' => $matchEvent->event_data['reason'] ?? null,
                    'foul_type' => $matchEvent->event_data['foul_type'] ?? null
                ]);

            case 'substitution':
                return array_merge($baseDetails, [
                    'player_out_id' => $matchEvent->event_data['player_out_id'] ?? null,
                    'player_out_name' => $matchEvent->event_data['player_out_name'] ?? null,
                    'reason' => $matchEvent->event_data['reason'] ?? 'tactical'
                ]);

            default:
                return array_merge($baseDetails, $matchEvent->event_data ?? []);
        }
    }

    /**
     * Determine match result
     *
     * @param int $homeScore
     * @param int $awayScore
     * @return string
     */
    protected static function determineResult(int $homeScore, int $awayScore): string
    {
        if ($homeScore > $awayScore) {
            return 'home_win';
        } elseif ($homeScore < $awayScore) {
            return 'away_win';
        } else {
            return 'draw';
        }
    }

    /**
     * Determine team result
     *
     * @param int $homeScore
     * @param int $awayScore
     * @param string $team
     * @return string
     */
    protected static function determineTeamResult(int $homeScore, int $awayScore, string $team): string
    {
        if ($team === 'home') {
            if ($homeScore > $awayScore) return 'win';
            if ($homeScore < $awayScore) return 'loss';
            return 'draw';
        } else {
            if ($awayScore > $homeScore) return 'win';
            if ($awayScore < $homeScore) return 'loss';
            return 'draw';
        }
    }

    /**
     * Get status change reason
     *
     * @param string $oldStatus
     * @param string $newStatus
     * @return string
     */
    protected static function getStatusChangeReason(string $oldStatus, string $newStatus): string
    {
        $reasons = [
            'scheduled->in_progress' => 'match_started',
            'in_progress->completed' => 'match_finished',
            'in_progress->postponed' => 'match_postponed',
            'postponed->in_progress' => 'match_resumed',
            'scheduled->cancelled' => 'match_cancelled',
            'in_progress->cancelled' => 'match_abandoned'
        ];

        $key = "{$oldStatus}->{$newStatus}";
        return $reasons[$key] ?? 'status_changed';
    }

    /**
     * Sanitize sensitive data for event payloads
     *
     * @param array $data
     * @return array
     */
    public static function sanitizePayload(array $data): array
    {
        $sensitiveFields = [
            'password', 'token', 'secret', 'api_key'
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }
}
