<?php

namespace App\Services\Events;

use App\Models\Team;
use App\Models\Player;
use Carbon\Carbon;

/**
 * Event Payload Builder for Team Service
 *
 * Helper class to build standardized event payloads
 */
class EventPayloadBuilder
{
    /**
     * Build team created event payload
     *
     * @param Team $team
     * @param array $user
     * @return array
     */
    public static function teamCreated(Team $team, array $user): array
    {
        return [
            'team_id' => $team->id,
            'name' => $team->name,
            'logo' => $team->logo,
            'tournament_id' => $team->tournament_id,
            'coach_id' => $team->coaches->first()?->id,
            'created_by' => $user['id'],
            'created_by_name' => $user['name'] ?? null,
            'created_at' => $team->created_at->toISOString(),
            'tournament' => [
                'id' => $team->tournament_id,
                'name' => $team->tournament?->name ?? null
            ]
        ];
    }

    /**
     * Build team updated event payload
     *
     * @param Team $team
     * @param array $oldData
     * @return array
     */
    public static function teamUpdated(Team $team, array $oldData): array
    {
        $newData = $team->toArray();

        return [
            'team_id' => $team->id,
            'name' => $team->name,
            'updated_fields' => array_keys(array_diff_assoc($newData, $oldData)),
            'changes' => [
                'old' => $oldData,
                'new' => $newData
            ],
            'updated_at' => now()->toISOString(),
            'tournament_id' => $team->tournament_id,
            'coach_id' => $team->coaches->first()?->id,
        ];
    }

    /**
     * Build team deleted event payload
     *
     * @param Team $team
     * @param array $user
     * @return array
     */
    public static function teamDeleted(Team $team, array $user): array
    {
        return [
            'team_id' => $team->id,
            'name' => $team->name,
            'tournament_id' => $team->tournament_id,
            'deleted_by' => $user['id'],
            'deleted_by_name' => $user['name'] ?? null,
            'deleted_at' => now()->toISOString(),
            'original_data' => [
                'logo' => $team->logo,
                'coach_id' => $team->coaches->first()?->id,
                'players_count' => $team->players->count(),
            ],
        ];
    }

    /**
     * Build player created event payload
     *
     * @param Player $player
     * @param array $user
     * @return array
     */
    public static function playerCreated(Player $player, array $user): array
    {
        return [
            'player_id' => $player->id,
            'full_name' => $player->full_name,
            'position' => $player->position,
            'jersey_number' => $player->jersey_number,
            'team_id' => $player->team_id,
            'created_by' => $user['id'],
            'created_by_name' => $user['name'] ?? null,
            'created_at' => $player->created_at->toISOString(),
            'team' => [
                'id' => $player->team_id,
                'name' => $player->team?->name ?? null
            ]
        ];
    }

    /**
     * Build player updated event payload
     *
     * @param Player $player
     * @param array $oldData
     * @return array
     */
    public static function playerUpdated(Player $player, array $oldData): array
    {
        // Get only the fillable fields to avoid relationship issues
        $newData = [
            'id' => $player->id,
            'team_id' => $player->team_id,
            'full_name' => $player->full_name,
            'position' => $player->position,
            'jersey_number' => $player->jersey_number,
            'created_at' => $player->created_at?->toISOString(),
            'updated_at' => $player->updated_at?->toISOString(),
        ];

        // Normalize old data to match structure
        $normalizedOldData = [
            'id' => $oldData['id'] ?? null,
            'team_id' => $oldData['team_id'] ?? null,
            'full_name' => $oldData['full_name'] ?? null,
            'position' => $oldData['position'] ?? null,
            'jersey_number' => $oldData['jersey_number'] ?? null,
            'created_at' => isset($oldData['created_at']) ? (is_string($oldData['created_at']) ? $oldData['created_at'] : $oldData['created_at']->toISOString()) : null,
            'updated_at' => isset($oldData['updated_at']) ? (is_string($oldData['updated_at']) ? $oldData['updated_at'] : $oldData['updated_at']->toISOString()) : null,
        ];

        // Find changed fields
        $updatedFields = [];
        foreach ($newData as $key => $value) {
            if ($key === 'updated_at') {
                continue; // Skip updated_at as it always changes
            }
            if (($normalizedOldData[$key] ?? null) !== $value) {
                $updatedFields[] = $key;
            }
        }

        return [
            'player_id' => $player->id,
            'full_name' => $player->full_name,
            'updated_fields' => $updatedFields,
            'changes' => [
                'old' => $normalizedOldData,
                'new' => $newData
            ],
            'updated_at' => now()->toISOString(),
            'team_id' => $player->team_id,
            'tournament_id' => $player->team->tournament_id ?? null,
            'position' => $player->position,
            'jersey_number' => $player->jersey_number,
        ];
    }

    /**
     * Build player deleted event payload
     *
     * @param Player $player
     * @param array $user
     * @return array
     */
    public static function playerDeleted(Player $player, array $user): array
    {
        return [
            'player_id' => $player->id,
            'full_name' => $player->full_name,
            'position' => $player->position,
            'jersey_number' => $player->jersey_number,
            'team_id' => $player->team_id,
            'deleted_by' => $user['id'],
            'deleted_by_name' => $user['name'] ?? null,
            'deleted_at' => now()->toISOString(),
            'original_data' => [
                'team_name' => $player->team?->name ?? null,
            ],
        ];
    }

    /**
     * Build player transferred event payload
     *
     * @param Player $player
     * @param int $oldTeamId
     * @param int $newTeamId
     * @param array $user
     * @return array
     */
    public static function playerTransferred(Player $player, int $oldTeamId, int $newTeamId, array $user): array
    {
        return [
            'player_id' => $player->id,
            'full_name' => $player->full_name,
            'old_team_id' => $oldTeamId,
            'new_team_id' => $newTeamId,
            'transferred_by' => $user['id'],
            'transferred_by_name' => $user['name'] ?? null,
            'transferred_at' => now()->toISOString(),
            'position' => $player->position,
            'jersey_number' => $player->jersey_number,
            'reason' => 'team_transfer'
        ];
    }

    /**
     * Build player status changed event payload
     *
     * @param Player $player
     * @param string $oldStatus
     * @return array
     */
    public static function playerStatusChanged(Player $player, string $oldStatus): array
    {
        return [
            'player_id' => $player->id,
            'full_name' => $player->full_name,
            'old_status' => $oldStatus,
            'new_status' => $player->status ?? null,
            'status_changed_at' => now()->toISOString(),
            'team_id' => $player->team_id,
            'position' => $player->position,
            'jersey_number' => $player->jersey_number,
            'reason' => self::getStatusChangeReason($oldStatus, $player->status ?? ''),
        ];
    }

    /**
     * Build team coach assigned event payload
     *
     * @param Team $team
     * @param int $coachId
     * @param array $user
     * @return array
     */
    public static function teamCoachAssigned(Team $team, int $coachId, array $user): array
    {
        return [
            'team_id' => $team->id,
            'name' => $team->name,
            'coach_id' => $coachId,
            'assigned_by' => $user['id'],
            'assigned_by_name' => $user['name'] ?? null,
            'assigned_at' => now()->toISOString(),
            'tournament_id' => $team->tournament_id,
            'action' => 'coach_assigned'
        ];
    }

    /**
     * Build team coach removed event payload
     *
     * @param Team $team
     * @param int $coachId
     * @param array $user
     * @return array
     */
    public static function teamCoachRemoved(Team $team, int $coachId, array $user): array
    {
        return [
            'team_id' => $team->id,
            'name' => $team->name,
            'coach_id' => $coachId,
            'removed_by' => $user['id'],
            'removed_by_name' => $user['name'] ?? null,
            'removed_at' => now()->toISOString(),
            'tournament_id' => $team->tournament_id,
            'action' => 'coach_removed'
        ];
    }

    /**
     * Get status change reason
     *
     * @param string $oldStatus
     * @param string $newStatus
     * @return string
     */
    private static function getStatusChangeReason(string $oldStatus, string $newStatus): string
    {
        $reasons = [
            'inactive->active' => 'player_activated',
            'active->inactive' => 'player_deactivated',
            'active->injured' => 'player_injured',
            'injured->active' => 'player_recovered',
            'active->suspended' => 'player_suspended',
            'suspended->active' => 'player_unsuspended',
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
