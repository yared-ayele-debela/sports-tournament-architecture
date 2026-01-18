<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tournament extends Model
{
    protected $fillable = [
        'sport_id',
        'name',
        'location',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'sport_id' => 'integer',
    ];

    /**
     * Get the sport that owns the tournament.
     */
    public function sport(): BelongsTo
    {
        return $this->belongsTo(Sport::class);
    }

    /**
     * Get the settings associated with the tournament.
     */
    public function settings(): HasOne
    {
        return $this->hasOne(TournamentSettings::class);
    }

    /**
     * Get all teams for this tournament (external reference).
     * Note: Teams are managed by a separate service.
     */
    public function teams(): HasMany
    {
        // This will be implemented via API calls to Team Service
        // For now, return an empty relationship
        return $this->hasMany(Team::class);
    }

    /**
     * Get all matches for this tournament (external reference).
     * Note: Matches are managed by a separate service.
     */
    public function matches(): HasMany
    {
        // This will be implemented via API calls to Match Service
        // For now, return an empty relationship
        return $this->hasMany(Match::class);
    
    }
}
