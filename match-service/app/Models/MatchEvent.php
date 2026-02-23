<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Http;

class MatchEvent extends Model
{
    protected $fillable = [
        'match_id',
        'team_id',
        'player_id',
        'event_type',
        'minute',
        'description',
    ];

    protected $casts = [
        'match_id' => 'integer',
        'team_id' => 'integer',
        'player_id' => 'integer',
        'minute' => 'integer',
    ];

    public function match(): BelongsTo
    {
        return $this->belongsTo(MatchGame::class, 'match_id');
    }

    public function getTeam()
    {
        $teamServiceUrl = config('services.team_service.url', env('TEAM_SERVICE_URL', 'http://team-service:8003'));
        $response = Http::get("{$teamServiceUrl}/api/teams/{$this->team_id}");
        return $response->successful() ? $response->json() : null;
    }

    public function getPlayer()
    {
        $teamServiceUrl = config('services.team_service.url', env('TEAM_SERVICE_URL', 'http://team-service:8003'));
        $response = Http::get("{$teamServiceUrl}/api/players/{$this->player_id}");
        return $response->successful() ? $response->json() : null;
    }
}
