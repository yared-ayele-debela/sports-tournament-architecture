<?php

namespace App\Policies;

use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PlayerPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Player $player): bool
    {
        return $user->teams()->where('team_id', $player->team_id)->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Team $team = null): bool
    {
        if ($team) {
            return $user->teams()->where('team_id', $team->id)->exists();
        }
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Player $player): bool
    {
        return $user->teams()->where('team_id', $player->team_id)->exists();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Player $player): bool
    {
        return $user->teams()->where('team_id', $player->team_id)->exists();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Player $player): bool
    {
        return false; // Coaches cannot restore players, only admins
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Player $player): bool
    {
        return false; // Coaches cannot permanently delete players, only admins
    }
}
