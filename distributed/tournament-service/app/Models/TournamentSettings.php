<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentSettings extends Model
{
    protected $fillable = [
        'tournament_id',
        'match_duration',
        'win_rest_time',
        'daily_start_time',
        'daily_end_time',
    ];

    protected $casts = [
        'match_duration' => 'integer',
        'win_rest_time' => 'integer',
        'daily_start_time' => 'datetime:H:i',
        'daily_end_time' => 'datetime:H:i',
        'tournament_id' => 'integer',
    ];

    /**
     * Get the tournament that owns the settings.
     */
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }
}
