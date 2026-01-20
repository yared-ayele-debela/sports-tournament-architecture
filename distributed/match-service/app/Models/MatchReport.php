<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchReport extends Model
{
    protected $fillable = [
        'match_id',
        'summary',
        'referee',
        'attendance',
    ];

    protected $casts = [
        'match_id' => 'integer',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchGame::class, 'match_id');
    }
}
