<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Sport;
use App\Models\TournamentSettings;
use App\Models\Team;
use App\Models\MatchModel;
use App\Models\Standing;

class Tournament extends Model
{
    protected $fillable = [
        'sport_id',
        'name',
        'location',
        'start_date',
        'end_date',
        'status',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function settings()
    {
        return $this->hasOne(TournamentSettings::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function matches()
    {
        return $this->hasMany(MatchModel::class);
    }

    public function standings()
    {
        return $this->hasMany(Standing::class);
    }
}
