<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tournament;
use App\Models\Player;
use App\Models\MatchModel;
use App\Models\MatchEvent;
use App\Models\Standing;

class Team extends Model
{
    protected $fillable = [
        'tournament_id',
        'name',
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

    public function homeMatches()
    {
        return $this->hasMany(MatchModel::class, 'home_team_id');
    }

    public function awayMatches()
    {
        return $this->hasMany(MatchModel::class, 'away_team_id');
    }

    public function matchEvents()
    {
        return $this->hasMany(MatchEvent::class);
    }

    public function matches()
    {
        return $this->hasMany(MatchModel::class, 'home_team_id')
            ->orWhere('away_team_id', $this->id);
    }

    /**
     * Get the coaches that belong to the team.
     */
    public function coaches()
    {
        return $this->belongsToMany(User::class, 'team_coaches', 'team_id', 'user_id');
    }

    public function standings()
    {
        return $this->hasMany(Standing::class);
    }
}
