<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tournament;
use App\Models\Venue;
use App\Models\Team;
use App\Models\MatchEvent;
use App\Models\MatchReport;

class MatchModel extends Model
{
    protected $table = 'matches';

    protected $fillable = [
        'tournament_id',
        'venue_id',
        'home_team_id',
        'away_team_id',
        'referee_id',
        'match_date',
        'round_number',
        'status',
        'home_score',
        'away_score',
        'current_minute',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'match_date' => 'datetime',
        'round_number' => 'integer',
        'home_score' => 'integer',
        'away_score' => 'integer',
        'current_minute' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function referee()
    {
        return $this->belongsTo(User::class, 'referee_id');
    }
    public function matchEvents()
{
    return $this->hasMany(MatchEvent::class, 'match_id');
}

    public function matchReport()
    {
        return $this->hasOne(MatchReport::class, 'match_id');
    }
    // Match.php
public function statusBadgeClasses(): string
{
    return match($this->status) {
        'completed'   => 'bg-green-100 text-green-800',
        'scheduled'   => 'bg-blue-100 text-blue-800',
        'in_progress' => 'bg-yellow-100 text-yellow-800',
        'cancelled'   => 'bg-red-100 text-red-800',
        default       => 'bg-gray-100 text-gray-800',
    };
}

}
