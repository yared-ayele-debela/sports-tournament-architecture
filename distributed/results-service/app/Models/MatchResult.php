<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\Clients\TeamServiceClient;

class MatchResult extends Model
{
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
        return $teamService->getTeam($this->home_team_id);
    }

    public function getAwayTeam()
    {
        $teamService = new TeamServiceClient();
        return $teamService->getTeam($this->away_team_id);
    }
}
