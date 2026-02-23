<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Team extends Model
{
    protected $fillable = [
        'tournament_id',
        'name',
        'logo',
    ];

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    /**
     * Get coach user IDs from pivot table
     * Note: User data is stored in auth-service, not in team-service
     */
    public function getCoachIds(): array
    {
        return DB::table('team_coach')
            ->where('team_id', $this->id)
            ->pluck('user_id')
            ->toArray();
    }

    /**
     * Check if a user is a coach for this team
     * Note: User data is stored in auth-service, not in team-service
     */
    public function isCoach($userId): bool
    {
        return DB::table('team_coach')
            ->where('team_id', $this->id)
            ->where('user_id', $userId)
            ->exists();
    }
}
