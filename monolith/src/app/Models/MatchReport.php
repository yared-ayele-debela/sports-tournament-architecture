<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\MatchModel;

class MatchReport extends Model
{
    protected $fillable = [
        'match_id',
        'summary',
        'referee',
        'attendance',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function match()
    {
        return $this->belongsTo(MatchModel::class);
    }
}
