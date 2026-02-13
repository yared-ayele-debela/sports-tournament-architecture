<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\Clients\TeamServiceClient;

class MatchResult extends Model
{
    protected $appends = [
        'homeTeam',
        'awayTeam',
    ];

    protected $fillable = [
        'match_id',
        'tournament_id',
        'home_team_id',
        'away_team_id',
        'home_score',
        'away_score',
        'completed_at',
    ];

    protected $casts = [
        'match_id' => 'integer',
        'tournament_id' => 'integer',
        'home_team_id' => 'integer',
        'away_team_id' => 'integer',
        'home_score' => 'integer',
        'away_score' => 'integer',
        'completed_at' => 'datetime',
    ];

    public function getHomeTeam()
    {
        $teamService = new TeamServiceClient();
        // Use getPublicTeam which returns null instead of throwing exceptions for missing teams
        return $teamService->getPublicTeam($this->home_team_id);
    }

    public function getHomeTeamAttribute()
    {
        return $this->getHomeTeam();
    }

    public function getAwayTeam()
    {
        $teamService = new TeamServiceClient();
        // Use getPublicTeam which returns null instead of throwing exceptions for missing teams
        return $teamService->getPublicTeam($this->away_team_id);
    }

    public function getAwayTeamAttribute()
    {
        return $this->getAwayTeam();
    }
}
