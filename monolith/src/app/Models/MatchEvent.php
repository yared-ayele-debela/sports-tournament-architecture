<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\MatchModel;
use App\Models\Team;
use App\Models\Player;

class MatchEvent extends Model
{
    protected $fillable = [
        'match_id',
        'team_id',
        'player_id',
        'event_type',
        'minute',
        'description',
        'created_at'
    ];

    protected $casts = [
        'minute' => 'integer',
        'created_at' => 'datetime'
    ];

    public function match()
    {
        return $this->belongsTo(MatchModel::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function player()
    {
        return $this->belongsTo(Player::class);
    }
}
