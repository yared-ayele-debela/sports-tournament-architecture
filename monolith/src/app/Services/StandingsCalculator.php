<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\Team;
use App\Models\Standing;
use App\Models\MatchModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class StandingsCalculator
{
    public function calculateTournamentStandings(Tournament $tournament): void
    {
        DB::transaction(function () use ($tournament) {
            $teams = $tournament->teams;
            
            foreach ($teams as $team) {
                $this->calculateTeamStanding($tournament, $team);
            }
            
            $this->clearCache($tournament);
        });
    }

    public function calculateTeamStanding(Tournament $tournament, Team $team): Standing
    {
        $matches = MatchModel::where('tournament_id', $tournament->id)
            ->where(function ($query) use ($team) {
                $query->where('home_team_id', $team->id)
                      ->orWhere('away_team_id', $team->id);
            })
            ->where('status', 'completed')
            ->get();

        $played = $matches->count();
        $won = 0;
        $drawn = 0;
        $lost = 0;
        $goalsFor = 0;
        $goalsAgainst = 0;
        $points = 0;

        foreach ($matches as $match) {
            $isHome = $match->home_team_id === $team->id;
            $teamScore = $isHome ? $match->home_score : $match->away_score;
            $opponentScore = $isHome ? $match->away_score : $match->home_score;

            $goalsFor += $teamScore;
            $goalsAgainst += $opponentScore;

            if ($teamScore > $opponentScore) {
                $won++;
                $points += 3;
            } elseif ($teamScore === $opponentScore) {
                $drawn++;
                $points += 1;
            } else {
                $lost++;
            }
        }

        return Standing::updateOrCreate(
            ['tournament_id' => $tournament->id, 'team_id' => $team->id],
            [
                'played' => $played,
                'won' => $won,
                'drawn' => $drawn,
                'lost' => $lost,
                'goals_for' => $goalsFor,
                'goals_against' => $goalsAgainst,
                'points' => $points,
            ]
        );
    }

    public function getTournamentStandings(Tournament $tournament): array
    {
        $cacheKey = "tournament_standings_{$tournament->id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($tournament) {
            return $tournament->standings()
                ->with('team')
                ->orderBy('points', 'desc')
                ->orderBy('goals_for', 'desc')
                ->orderBy('goals_against', 'asc')
                ->get()
                ->toArray();
        });
    }

    private function clearCache(Tournament $tournament): void
    {
        $cacheKey = "tournament_standings_{$tournament->id}";
        Cache::forget($cacheKey);
        
        if (Redis::exists($cacheKey)) {
            Redis::del($cacheKey);
        }
    }

    public function recalculateAllStandings(): void
    {
        $tournaments = Tournament::all();
        
        foreach ($tournaments as $tournament) {
            $this->calculateTournamentStandings($tournament);
        }
    }
}
