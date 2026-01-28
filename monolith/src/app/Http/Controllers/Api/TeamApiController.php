<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeamApiController extends Controller
{
    /**
     * List teams with optional tournament / search filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Team::with(['tournament', 'players'])
            ->orderBy('name');

        if ($request->filled('tournament')) {
            $query->where('tournament_id', $request->tournament);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $teams = $query->paginate(20);

        return response()->json($teams);
    }

    /**
     * Show a single team with stats and related data.
     */
    public function show(int $id): JsonResponse
    {
        $team = Team::with([
                'tournament',
                'players',
                'homeMatches.awayTeam',
                'homeMatches.venue',
                'awayMatches.homeTeam',
                'awayMatches.venue',
            ])
            ->findOrFail($id);

        $allMatches = collect()
            ->merge($team->homeMatches)
            ->merge($team->awayMatches)
            ->sortBy('match_date');

        $totalMatches = $allMatches->count();
        $wins = $allMatches->filter(function ($match) use ($team) {
            return ($match->home_team_id === $team->id && $match->home_score > $match->away_score) ||
                   ($match->away_team_id === $team->id && $match->away_score > $match->home_score);
        })->count();

        $draws = $allMatches->filter(function ($match) {
            return $match->home_score === $match->away_score;
        })->count();

        $losses = $totalMatches - $wins - $draws;

        $goalsFor = $allMatches->sum(function ($match) use ($team) {
            return $match->home_team_id === $team->id ? $match->home_score : $match->away_score;
        });

        $goalsAgainst = $allMatches->sum(function ($match) use ($team) {
            return $match->home_team_id === $team->id ? $match->away_score : $match->home_score;
        });

        $stats = [
            'total_matches' => $totalMatches,
            'wins' => $wins,
            'draws' => $draws,
            'losses' => $losses,
            'goals_for' => $goalsFor,
            'goals_against' => $goalsAgainst,
            'goal_difference' => $goalsFor - $goalsAgainst,
            'win_percentage' => $totalMatches > 0 ? round(($wins / $totalMatches) * 100, 1) : 0,
        ];

        return response()->json([
            'team' => $team,
            'stats' => $stats,
        ]);
    }

    /**
     * Players in a team.
     */
    public function players(int $id): JsonResponse
    {
        $team = Team::findOrFail($id);

        $players = $team->players()
            ->orderBy('name')
            ->get();

        return response()->json([
            'team' => $team,
            'players' => $players,
        ]);
    }
}

