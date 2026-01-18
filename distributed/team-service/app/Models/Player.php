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
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'jersey_number' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

   
}
