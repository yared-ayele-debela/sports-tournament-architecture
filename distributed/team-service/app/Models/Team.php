<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    protected $fillable = [
        'tournament_id',
        'name',
        'coach_name',
        'logo',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function players()
    {
        return $this->hasMany(Player::class);
    }

   

    /**
     * Get the coaches that belong to the team.
     */
    public function coaches()
    {
        return $this->belongsToMany(User::class, 'team_coaches', 'team_id', 'user_id');
    }

  
}
