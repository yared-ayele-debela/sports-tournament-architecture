<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Tournament;

class TournamentSettings extends Model
{
    protected $fillable = [
        'tournament_id',
        'match_duration',
        'win_rest_time',
        'daily_start_time',
        'daily_end_time',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'daily_start_time' => 'datetime',
        'daily_end_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }
}
