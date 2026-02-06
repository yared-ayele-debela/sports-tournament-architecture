<?php

namespace App\Services;

use App\Models\Team;
use App\Models\Tournament;
use App\Exceptions\BusinessLogicException;
use App\Exceptions\ResourceNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TeamService
{
    /**
     * Create a new team with logo upload and coach assignment
     */
    public function createTeam(array $data, ?\Illuminate\Http\UploadedFile $logoFile = null, ?int $coachId = null): Team
    {
        try {
            // Validate coach assignment if provided
            if ($coachId) {
                $this->validateCoachAssignment($coachId, $data['tournament_id']);
            }

            // Handle logo upload
            if ($logoFile) {
                $data['logo'] = $logoFile->store('team-logos', 'public');
            }

            $team = Team::create($data);

            // Attach coach if provided
            if ($coachId) {
                $team->coaches()->attach($coachId);
            }

            Log::info('Team created', [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'tournament_id' => $team->tournament_id,
                'user_id' => auth()->id(),
            ]);

            return $team;
        } catch (\Exception $e) {
            Log::error('Failed to create team', [
                'data' => $data,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            throw new BusinessLogicException(
                $e->getMessage(),
                'Failed to create team. Please try again.',
                ['data' => $data]
            );
        }
    }

    /**
     * Update team with logo management and coach sync
     */
    public function updateTeam(Team $team, array $data, ?\Illuminate\Http\UploadedFile $logoFile = null, ?int $coachId = null): Team
    {
        try {
            // Validate coach assignment if provided and different from current coach
            if ($coachId !== null) {
                $tournamentId = $data['tournament_id'] ?? $team->tournament_id;

                // Check if coach is already assigned to this team
                $currentCoachIds = $team->coaches->pluck('id')->toArray();

                // Only validate if coach is changing (not already assigned to this team)
                if (!in_array($coachId, $currentCoachIds)) {
                    $this->validateCoachAssignment($coachId, $tournamentId, $team->id);
                }
            }

            // Handle logo upload
            if ($logoFile) {
                // Delete old logo if exists
                if ($team->logo) {
                    Storage::disk('public')->delete($team->logo);
                }

                $data['logo'] = $logoFile->store('team-logos', 'public');
            }

            $team->update($data);

            // Sync coach relationship
            if ($coachId !== null) {
                $team->coaches()->sync([$coachId]);
            } else {
                $team->coaches()->detach();
            }

            Log::info('Team updated', [
                'team_id' => $team->id,
                'team_name' => $team->name,
                'user_id' => auth()->id(),
            ]);

            return $team->fresh();
        } catch (\Exception $e) {
            Log::error('Failed to update team', [
                'team_id' => $team->id,
                'data' => $data,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            throw new BusinessLogicException(
                $e->getMessage(),
                'Failed to update team. Please try again.',
                ['team_id' => $team->id, 'data' => $data]
            );
        }
    }

    /**
     * Delete team and associated logo
     */
    public function deleteTeam(Team $team): bool
    {
        try {
            $teamId = $team->id;
            $teamName = $team->name;

            // Delete logo if exists
            if ($team->logo) {
                Storage::disk('public')->delete($team->logo);
            }

            $deleted = $team->delete();

            Log::info('Team deleted', [
                'team_id' => $teamId,
                'team_name' => $teamName,
                'user_id' => auth()->id(),
            ]);

            return $deleted;
        } catch (\Exception $e) {
            Log::error('Failed to delete team', [
                'team_id' => $team->id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            throw new BusinessLogicException(
                $e->getMessage(),
                'Failed to delete team. Please try again.',
                ['team_id' => $team->id]
            );
        }
    }

    /**
     * Validate that team name is unique within tournament
     */
    public function validateTeamNameUniqueness(string $name, int $tournamentId, ?int $excludeTeamId = null): bool
    {
        $query = Team::where('tournament_id', $tournamentId)
            ->where('name', $name);

        if ($excludeTeamId) {
            $query->where('id', '!=', $excludeTeamId);
        }

        return !$query->exists();
    }

    /**
     * Validate that a coach is not already assigned to another team in the same tournament
     */
    protected function validateCoachAssignment(int $coachId, int $tournamentId, ?int $excludeTeamId = null): void
    {
        // Use direct query to check team_coaches pivot table
        $query = DB::table('team_coaches')
            ->join('teams', 'team_coaches.team_id', '=', 'teams.id')
            ->where('teams.tournament_id', $tournamentId)
            ->where('team_coaches.user_id', $coachId);

        if ($excludeTeamId) {
            $query->where('teams.id', '!=', $excludeTeamId);
        }

        $existingAssignment = $query->first();

        if ($existingAssignment) {
            $teamName = Team::find($existingAssignment->team_id)->name ?? 'Unknown Team';
            throw new BusinessLogicException(
                "Coach is already assigned to team '{$teamName}' in this tournament",
                "This coach is already assigned to another team in this tournament. A coach can only coach one team per tournament.",
                [
                    'coach_id' => $coachId,
                    'tournament_id' => $tournamentId,
                    'existing_team_id' => $existingAssignment->team_id,
                    'existing_team_name' => $teamName,
                ]
            );
        }
    }

    /**
     * Get teams for a tournament
     */
    public function getTeamsForTournament(Tournament $tournament)
    {
        return Team::where('tournament_id', $tournament->id)
            ->with(['tournament', 'coaches'])
            ->orderBy('name')
            ->get();
    }
}
