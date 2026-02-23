<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\Clients\TeamServiceClient;

class Standing extends Model
{
    protected $fillable = [
        'tournament_id',
        'team_id',
        'played',
        'won',
        'drawn',
        'lost',
        'goals_for',
        'goals_against',
        'points',
        'position',
        'goal_difference',
    ];

    protected $casts = [
        'played' => 'integer',
        'won' => 'integer',
        'drawn' => 'integer',
        'lost' => 'integer',
        'goals_for' => 'integer',
        'goals_against' => 'integer',
        'points' => 'integer',
        'position' => 'integer',
        'goal_difference' => 'integer',
    ];

    public function getGoalDifferenceAttribute()
    {
        return $this->goals_for - $this->goals_against;
    }

    public function getTeam()
    {
        $teamService = new TeamServiceClient();
        // Use getPublicTeam which returns null instead of throwing exceptions for missing teams
        return $teamService->getPublicTeam($this->team_id);
    }
}
