<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use App\Models\Sport;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StatsApiController extends Controller
{
    /**
     * High level dashboard data for the public home page.
     */
    public function dashboard(Request $request): JsonResponse
    {
        // Featured tournaments – same logic as public home controller
        $featuredTournaments = Tournament::with(['sport', 'teams'])
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->orderBy('start_date', 'desc')
            ->take(3)
            ->get();

        // Recent matches
        $recentMatches = MatchModel::with(['homeTeam', 'awayTeam', 'venue', 'tournament'])
            ->orderBy('match_date', 'desc')
            ->take(6)
            ->get();

        // Top teams by win rate (mirrors HomeController)
        $topTeams = Team::with(['tournament'])
            ->withCount(['homeMatches', 'awayMatches'])
            ->get()
            ->map(function ($team) {
                $totalMatches = $team->home_matches_count + $team->away_matches_count;
                $wins = MatchModel::where(function ($query) use ($team) {
                    $query->where('home_team_id', $team->id)
                          ->where('home_score', '>', 'away_score');
                })->orWhere(function ($query) use ($team) {
                    $query->where('away_team_id', $team->id)
                          ->where('away_score', '>', 'home_score');
                })->count();

                return [
                    'team' => $team,
                    'total_matches' => $totalMatches,
                    'wins' => $wins,
                    'win_rate' => $totalMatches > 0 ? round(($wins / $totalMatches) * 100, 1) : 0,
                ];
            })
            ->sortByDesc('win_rate')
            ->take(6)
            ->pluck('team')
            ->values();

        // Aggregate statistics
        $stats = [
            'total_tournaments' => Tournament::count(),
            'active_tournaments' => Tournament::where('start_date', '<=', now())
                ->where('end_date', '>=', now())->count(),
            'total_teams' => Team::count(),
            'total_matches' => MatchModel::count(),
            'total_players' => Player::count(),
        ];

        return response()->json([
            'featured_tournaments' => $featuredTournaments,
            'recent_matches' => $recentMatches,
            'top_teams' => $topTeams,
            'stats' => $stats,
        ]);
    }

    /**
     * List of featured / active tournaments.
     */
    public function featuredTournaments(): JsonResponse
    {
        $featuredTournaments = Tournament::with(['sport', 'teams'])
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->orderBy('start_date', 'desc')
            ->take(10)
            ->get();

        return response()->json($featuredTournaments);
    }

    /**
     * Recent matches across all tournaments.
     */
    public function recentMatches(): JsonResponse
    {
        $recentMatches = MatchModel::with(['homeTeam', 'awayTeam', 'venue', 'tournament'])
            ->orderBy('match_date', 'desc')
            ->take(20)
            ->get();

        return response()->json($recentMatches);
    }

    /**
     * Simple list of tournaments (for dropdowns, filters, etc.).
     */
    public function tournaments(): JsonResponse
    {
        $tournaments = Tournament::orderBy('name')
            ->get(['id', 'name', 'start_date', 'end_date', 'status']);

        return response()->json($tournaments);
    }

    /**
     * Simple list of sports.
     */
    public function sports(): JsonResponse
    {
        $sports = Sport::orderBy('name')->get(['id', 'name']);

        return response()->json($sports);
    }
}

