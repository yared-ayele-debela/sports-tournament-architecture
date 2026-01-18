<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamCoach extends Model
{
    protected $fillable = [
        'team_id',
        'user_id',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
