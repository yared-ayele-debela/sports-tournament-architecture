<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TeamPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine if the user can view the team.
     */
    public function view(User $user, Team $team): bool
    {
        return $user->teams()->where('team_id', $team->id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false; // Coaches cannot create teams, only admins
    }

    /**
     * Determine if the user can update the team.
     */
    public function update(User $user, Team $team): bool
    {
        return $user->teams()->where('team_id', $team->id)->exists();
    }

    /**
     * Determine if the user can delete the team.
     */
    public function delete(User $user, Team $team): bool
    {
        return $user->teams()->where('team_id', $team->id)->exists();
    }

    /**
     * Determine if the user can manage players in the team.
     */
    public function managePlayers(User $user, Team $team): bool
    {
        return $user->teams()->where('team_id', $team->id)->exists();
    }

    /**
     * Determine if the user can view the team's matches.
     */
    public function viewMatches(User $user, Team $team): bool
    {
        return $user->teams()->where('team_id', $team->id)->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Team $team): bool
    {
        return false; // Coaches cannot restore teams, only admins
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Team $team): bool
    {
        return false; // Coaches cannot permanently delete teams, only admins
    }
}
