<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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

    public function coaches()
    {
        return $this->belongsToMany(User::class, 'team_coach');
    }

    public function isCoach($userId): bool
    {
        return $this->coaches()->where('user_id', $userId)->exists();
    }
}
