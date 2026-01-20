<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venue extends Model
{
    protected $fillable = [
        'name',
        'location',
        'capacity'
    ];

    protected $casts = [
        'capacity' => 'integer'
    ];

    /**
     * Get matches for this venue (external reference).
     */
    // public function matches(): HasMany
    // {
    //     return $this->hasMany(Match::class);
    // }
}
