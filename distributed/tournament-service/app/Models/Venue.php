<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venue extends Model
{
    protected $fillable = [
        'name',
        'location',
        'capacity',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    /**
     * Get all matches for this venue (external reference).
     * Note: Matches are managed by a separate service.
     */
    public function matches(): HasMany
    {
        // This will be implemented via API calls to Match Service
        // For now, return an empty relationship
        return $this->hasMany(Match::class);
    }
}
