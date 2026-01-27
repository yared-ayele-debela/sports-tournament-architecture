<?php

namespace App\Services\Events;

use App\Models\Tournament;
use App\Models\Sport;
use App\Models\Venue;
use Carbon\Carbon;

/**
 * Event Payload Builder for Tournament Service
 * 
 * Helper class to build standardized event payloads
 */
class EventPayloadBuilder
{
    /**
     * Build tournament created event payload
     *
     * @param Tournament $tournament
     * @param array $user
     * @return array
     */
    public static function tournamentCreated(Tournament $tournament, array $user): array
    {
        return [
            'tournament_id' => $tournament->id,
            'name' => $tournament->name,
            'location' => $tournament->location,
            'start_date' => $tournament->start_date->toISOString(),
            'end_date' => $tournament->end_date->toISOString(),
            'status' => $tournament->status,
            'sport_id' => $tournament->sport_id,
            'sport' => $tournament->sport ? [
                'id' => $tournament->sport->id,
                'name' => $tournament->sport->name,
                'category' => $tournament->sport->category ?? null
            ] : null,
            'settings' => $tournament->settings ? [
                'team_size' => $tournament->settings->team_size,
                'duration' => $tournament->settings->duration,
                'scoring_system' => $tournament->settings->scoring_system,
                'rules' => $tournament->settings->rules
            ] : null,
            'created_by' => $user['id'],
            'created_by_name' => $user['name'] ?? null,
            'created_at' => $tournament->created_at->toISOString(),
        ];
    }

    /**
     * Build tournament updated event payload
     *
     * @param Tournament $tournament
     * @param array $oldData
     * @return array
     */
    public static function tournamentUpdated(Tournament $tournament, array $oldData): array
    {
        $newData = $tournament->toArray();
        
        return [
            'tournament_id' => $tournament->id,
            'name' => $tournament->name,
            'updated_fields' => array_keys(array_diff_assoc($newData, $oldData)),
            'changes' => [
                'old' => $oldData,
                'new' => $newData
            ],
            'updated_at' => now()->toISOString(),
            'status' => $tournament->status,
            'sport' => $tournament->sport ? [
                'id' => $tournament->sport->id,
                'name' => $tournament->sport->name
            ] : null,
        ];
    }

    /**
     * Build tournament status changed event payload
     *
     * @param Tournament $tournament
     * @param string $oldStatus
     * @return array
     */
    public static function tournamentStatusChanged(Tournament $tournament, string $oldStatus): array
    {
        return [
            'tournament_id' => $tournament->id,
            'name' => $tournament->name,
            'old_status' => $oldStatus,
            'new_status' => $tournament->status,
            'status_changed_at' => now()->toISOString(),
            'start_date' => $tournament->start_date->toISOString(),
            'end_date' => $tournament->end_date->toISOString(),
            'sport' => $tournament->sport ? [
                'id' => $tournament->sport->id,
                'name' => $tournament->sport->name
            ] : null,
            'transition_reason' => self::getStatusTransitionReason($oldStatus, $tournament->status),
        ];
    }

    /**
     * Build sport created event payload
     *
     * @param Sport $sport
     * @param array $user
     * @return array
     */
    public static function sportCreated(Sport $sport, array $user): array
    {
        return [
            'sport_id' => $sport->id,
            'name' => $sport->name,
            'category' => $sport->category,
            'description' => $sport->description,
            'rules' => $sport->rules,
            'created_by' => $user['id'],
            'created_by_name' => $user['name'] ?? null,
            'created_at' => $sport->created_at->toISOString(),
        ];
    }

    /**
     * Build sport updated event payload
     *
     * @param Sport $sport
     * @param array $oldData
     * @return array
     */
    public static function sportUpdated(Sport $sport, array $oldData): array
    {
        $newData = $sport->toArray();
        
        return [
            'sport_id' => $sport->id,
            'name' => $sport->name,
            'updated_fields' => array_keys(array_diff_assoc($newData, $oldData)),
            'changes' => [
                'old' => $oldData,
                'new' => $newData
            ],
            'updated_at' => now()->toISOString(),
        ];
    }

    /**
     * Build venue created event payload
     *
     * @param Venue $venue
     * @param array $user
     * @return array
     */
    public static function venueCreated(Venue $venue, array $user): array
    {
        return [
            'venue_id' => $venue->id,
            'name' => $venue->name,
            'address' => $venue->address,
            'city' => $venue->city,
            'country' => $venue->country,
            'capacity' => $venue->capacity,
            'surface_type' => $venue->surface_type,
            'facilities' => $venue->facilities,
            'coordinates' => $venue->coordinates ? [
                'latitude' => $venue->coordinates['latitude'],
                'longitude' => $venue->coordinates['longitude']
            ] : null,
            'created_by' => $user['id'],
            'created_by_name' => $user['name'] ?? null,
            'created_at' => $venue->created_at->toISOString(),
        ];
    }

    /**
     * Build venue updated event payload
     *
     * @param Venue $venue
     * @param array $oldData
     * @return array
     */
    public static function venueUpdated(Venue $venue, array $oldData): array
    {
        $newData = $venue->toArray();
        
        return [
            'venue_id' => $venue->id,
            'name' => $venue->name,
            'updated_fields' => array_keys(array_diff_assoc($newData, $oldData)),
            'changes' => [
                'old' => $oldData,
                'new' => $newData
            ],
            'updated_at' => now()->toISOString(),
        ];
    }

    /**
     * Build tournament deleted event payload
     *
     * @param Tournament $tournament
     * @param array $user
     * @return array
     */
    public static function tournamentDeleted(Tournament $tournament, array $user): array
    {
        return [
            'tournament_id' => $tournament->id,
            'name' => $tournament->name,
            'deleted_by' => $user['id'],
            'deleted_by_name' => $user['name'] ?? null,
            'deleted_at' => now()->toISOString(),
            'original_data' => [
                'status' => $tournament->status,
                'start_date' => $tournament->start_date->toISOString(),
                'end_date' => $tournament->end_date->toISOString(),
                'sport_id' => $tournament->sport_id,
                'location' => $tournament->location,
            ],
        ];
    }

    /**
     * Build sport deleted event payload
     *
     * @param Sport $sport
     * @param array $user
     * @return array
     */
    public static function sportDeleted(Sport $sport, array $user): array
    {
        return [
            'sport_id' => $sport->id,
            'name' => $sport->name,
            'category' => $sport->category,
            'deleted_by' => $user['id'],
            'deleted_by_name' => $user['name'] ?? null,
            'deleted_at' => now()->toISOString(),
        ];
    }

    /**
     * Build venue deleted event payload
     *
     * @param Venue $venue
     * @param array $user
     * @return array
     */
    public static function venueDeleted(Venue $venue, array $user): array
    {
        return [
            'venue_id' => $venue->id,
            'name' => $venue->name,
            'city' => $venue->city,
            'country' => $venue->country,
            'deleted_by' => $user['id'],
            'deleted_by_name' => $user['name'] ?? null,
            'deleted_at' => now()->toISOString(),
        ];
    }

    /**
     * Get status transition reason
     *
     * @param string $oldStatus
     * @param string $newStatus
     * @return string
     */
    private static function getStatusTransitionReason(string $oldStatus, string $newStatus): string
    {
        $reasons = [
            'planned->ongoing' => 'tournament_started',
            'ongoing->completed' => 'tournament_finished',
            'ongoing->cancelled' => 'tournament_cancelled',
            'planned->cancelled' => 'tournament_cancelled_before_start',
            'cancelled->planned' => 'tournament_rescheduled',
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

    /**
     * Build tournament settings updated event payload
     *
     * @param Tournament $tournament
     * @param array $oldSettings
     * @param array $newSettings
     * @return array
     */
    public static function tournamentSettingsUpdated(Tournament $tournament, array $oldSettings, array $newSettings): array
    {
        return [
            'tournament_id' => $tournament->id,
            'name' => $tournament->name,
            'updated_settings' => array_keys(array_diff_assoc($newSettings, $oldSettings)),
            'changes' => [
                'old' => $oldSettings,
                'new' => $newSettings
            ],
            'updated_at' => now()->toISOString(),
        ];
    }
}
