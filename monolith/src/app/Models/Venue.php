<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\MatchModel;

class Venue extends Model
{
    protected $fillable = [
        'name',
        'location',
        'capacity',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'capacity' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function matches()
    {
        return $this->hasMany(MatchModel::class);
    }
}
