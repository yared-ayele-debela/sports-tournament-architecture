<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sport extends Model
{
    protected $fillable = [
        'name',
        'team_based',
        'rules',
        'description',
    ];

    protected $casts = [
        'team_based' => 'boolean',
        'rules' => 'text',
        'description' => 'text',
    ];

    /**
     * Get all tournaments for this sport.
     */
    public function tournaments(): HasMany
    {
        return $this->hasMany(Tournament::class);
    }
}
