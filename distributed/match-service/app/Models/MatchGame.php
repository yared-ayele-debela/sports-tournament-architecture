<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Http;

class MatchGame extends Model
{
    protected $table = 'matches';
    protected $fillable = [
        'tournament_id',
        'venue_id',
        'home_team_id',
        'away_team_id',
        'referee_id',
        'match_date',
        'round_number',
        'status',
        'home_score',
        'away_score',
        'current_minute',
    ];

    protected $casts = [
        'match_date' => 'datetime',
        'home_score' => 'integer',
        'away_score' => 'integer',
        'current_minute' => 'integer',
        'round_number' => 'integer',
    ];

    public function matchEvents(): HasMany
    {
        return $this->hasMany(MatchEvent::class, 'match_id');
    }

    public function matchReport(): HasOne
    {
        return $this->hasOne(MatchReport::class, 'match_id');
    }

    public function getHomeTeam()
    {
        $teamServiceUrl = config('services.team_service.url', env('TEAM_SERVICE_URL', 'http://team-service:8003'));
        $response = Http::get("{$teamServiceUrl}/api/teams/{$this->home_team_id}");
        return $response->successful() ? $response->json() : null;
    }

    public function getAwayTeam()
    {
        $teamServiceUrl = config('services.team_service.url', env('TEAM_SERVICE_URL', 'http://team-service:8003'));
        $response = Http::get("{$teamServiceUrl}/api/teams/{$this->away_team_id}");
        return $response->successful() ? $response->json() : null;
    }

    public function getTournament()
    {
        $tournamentServiceUrl = config('services.tournament_service.url', env('TOURNAMENT_SERVICE_URL', 'http://tournament-service:8002'));
        $response = Http::get("{$tournamentServiceUrl}/api/tournaments/{$this->tournament_id}");
        return $response->successful() ? $response->json() : null;
    }

    public function getVenue()
    {
        $tournamentServiceUrl = config('services.tournament_service.url', env('TOURNAMENT_SERVICE_URL', 'http://tournament-service:8002'));
        $response = Http::get("{$tournamentServiceUrl}/api/venues/{$this->venue_id}");
        return $response->successful() ? $response->json() : null;
    }
}
