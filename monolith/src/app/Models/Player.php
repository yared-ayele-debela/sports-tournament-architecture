<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Team;
use App\Models\MatchEvent;

class Player extends Model
{
    protected $fillable = [
        'team_id',
        'full_name',
        'position',
        'jersey_number',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'jersey_number' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function matchEvents()
    {
        return $this->hasMany(MatchEvent::class);
    }

    /**
     * Get the player's name (alias for full_name)
     * This allows using $player->name instead of $player->full_name
     */
    public function getNameAttribute()
    {
        return $this->full_name ?? 'Unknown Player';
    }
}
