<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sport extends Model
{
    protected $fillable = [
        'name',
        'team_based',

        'rules',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'team_based' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function tournaments()
    {
        return $this->hasMany(Tournament::class);
    }
}
