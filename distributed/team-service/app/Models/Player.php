<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    protected $fillable = [
        'team_id',
        'full_name',
        'position',
        'jersey_number',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
