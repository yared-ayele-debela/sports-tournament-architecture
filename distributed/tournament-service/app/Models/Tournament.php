<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    protected $fillable = [
        'sport_id',
        'name',
        'location',
        'start_date',
        'end_date',
        'status',
        'created_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'sport_id' => 'integer'
    ];

    /**
     * Get the sport that owns this tournament.
     */
    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    /**
     * Get the settings for this tournament.
     */
    public function settings(): HasOne
    {
        return $this->hasOne(TournamentSettings::class);
    }

    /**
     * Get teams for this tournament (external reference).
     */
    public function teams(): HasMany
    {
        return $this->hasMany('App\Models\Team');
    }

    /**
     * Get matches for this tournament (external reference).
     */
    public function matches(): HasMany
    {
        return $this->hasMany('App\Models\Match');
    }
}
