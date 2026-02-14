<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Standing;
use App\Models\MatchResult;
use App\Services\Clients\TeamServiceClient;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StatisticsController extends Controller
{
    protected TeamServiceClient $teamService;

    public function __construct(TeamServiceClient $teamService)
    {
        $this->teamService = $teamService;
    }

    /**
     * Get general statistics for results service
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $statistics = [
                'standings' => $this->getStandingStatistics(),
                'match_results' => $this->getMatchResultStatistics(),
            ];

            return ApiResponse::success($statistics, 'Statistics retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving statistics: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return ApiResponse::serverError('Failed to retrieve statistics', $e);
        }
    }

    /**
     * Get standing statistics
     *
     * @return array
     */
    private function getStandingStatistics(): array
    {
        try {
            $total = Standing::count();
            $uniqueTournaments = Standing::distinct('tournament_id')->count('tournament_id');
            $uniqueTeams = Standing::distinct('team_id')->count('team_id');

            return [
                'total' => $total,
                'unique_tournaments' => $uniqueTournaments,
                'unique_teams' => $uniqueTeams,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting standing statistics: ' . $e->getMessage());
            return ['total' => 0, 'unique_tournaments' => 0, 'unique_teams' => 0, 'error' => 'Unable to retrieve standing statistics'];
        }
    }

    /**
     * Get match result statistics
     *
     * @return array
     */
    private function getMatchResultStatistics(): array
    {
        try {
            $total = MatchResult::count();
            $uniqueTournaments = MatchResult::distinct('tournament_id')->count('tournament_id');

            // Calculate total goals
            $totalGoals = MatchResult::sum(DB::raw('home_score + away_score'));
            $averageGoals = $total > 0 ? round($totalGoals / $total, 2) : 0;

            return [
                'total' => $total,
                'unique_tournaments' => $uniqueTournaments,
                'total_goals' => $totalGoals,
                'average_goals_per_match' => $averageGoals,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting match result statistics: ' . $e->getMessage());
            return [
                'total' => 0,
                'unique_tournaments' => 0,
                'total_goals' => 0,
                'average_goals_per_match' => 0,
                'error' => 'Unable to retrieve match result statistics'
            ];
        }
    }

    public function teamStatistics(Request $request, int $teamId): JsonResponse
    {
        // Get team info
        $team = $this->teamService->getTeam($teamId);
        if (!$team) {
            return ApiResponse::notFound('Team not found');
        }

        // Calculate team statistics
        $standings = Standing::where('team_id', $teamId)->get();
        $matchResults = MatchResult::where(function ($query) use ($teamId) {
            $query->where('home_team_id', $teamId)
                  ->orWhere('away_team_id', $teamId);
        })->get();

        $totalMatches = $standings->sum('played');
        $totalWins = $standings->sum('won');
        $totalDraws = $standings->sum('drawn');
        $totalLosses = $standings->sum('lost');
        $totalGoalsFor = $standings->sum('goals_for');
        $totalGoalsAgainst = $standings->sum('goals_against');
        $totalPoints = $standings->sum('points');

        // Calculate win rate
        $winRate = $totalMatches > 0 ? round(($totalWins / $totalMatches) * 100, 2) : 0;

        // Recent form (last 5 matches)
        $recentResults = $matchResults->take(5)->map(function ($result) use ($teamId) {
            if ($result->home_team_id == $teamId) {
                return $result->home_score > $result->away_score ? 'W' :
                       ($result->home_score < $result->away_score ? 'L' : 'D');
            } else {
                return $result->away_score > $result->home_score ? 'W' :
                       ($result->away_score < $result->home_score ? 'L' : 'D');
            }
        })->implode('');

        return ApiResponse::success([
            'team' => $team,
            'statistics' => [
                'total_matches' => $totalMatches,
                'wins' => $totalWins,
                'draws' => $totalDraws,
                'losses' => $totalLosses,
                'goals_for' => $totalGoalsFor,
                'goals_against' => $totalGoalsAgainst,
                'goal_difference' => $totalGoalsFor - $totalGoalsAgainst,
                'points' => $totalPoints,
                'win_rate' => $winRate,
                'recent_form' => $recentResults,
            ],
        ]);
    }

    public function tournamentStatistics(Request $request, int $tournamentId): JsonResponse
    {
        $standings = Standing::where('tournament_id', $tournamentId)->get();
        $matchResults = MatchResult::where('tournament_id', $tournamentId)->get();

        // Calculate tournament statistics
        $totalMatches = $standings->sum('played');
        $totalGoals = $matchResults->sum(function ($result) {
            return $result->home_score + $result->away_score;
        });

        // Top scorer (simplified - would need match events data)
        $topScorer = [
            'player_name' => 'N/A',
            'goals' => 0,
        ];

        // Best defense (fewest goals conceded)
        $bestDefense = $standings->sortBy('goals_against')->first();

        // Best attack (most goals scored)
        $bestAttack = $standings->sortByDesc('goals_for')->first();

        return ApiResponse::success([
            'tournament_id' => $tournamentId,
            'total_matches' => $totalMatches,
            'total_goals' => $totalGoals,
            'average_goals_per_match' => $totalMatches > 0 ? round($totalGoals / $totalMatches, 2) : 0,
            'teams_participating' => $standings->count(),
            'top_scorer' => $topScorer,
            'best_defense' => $bestDefense ? [
                'team_id' => $bestDefense->team_id,
                'goals_conceded' => $bestDefense->goals_against,
            ] : null,
            'best_attack' => $bestAttack ? [
                'team_id' => $bestAttack->team_id,
                'goals_scored' => $bestAttack->goals_for,
            ] : null,
        ]);
    }

    /**
     * Get goals per tournament for chart
     *
     * @return JsonResponse
     */
    public function goalsPerTournament(): JsonResponse
    {
        try {
            $goalsPerTournament = MatchResult::select('tournament_id', DB::raw('SUM(home_score + away_score) as total_goals'))
                ->groupBy('tournament_id')
                ->orderByDesc('total_goals')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'tournament_id' => $item->tournament_id,
                        'total_goals' => (int) $item->total_goals,
                    ];
                })
                ->toArray();

            return ApiResponse::success($goalsPerTournament, 'Goals per tournament retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error getting goals per tournament: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to retrieve goals per tournament', $e);
        }
    }

    /**
     * Get top scoring teams for chart
     *
     * @return JsonResponse
     */
    public function topScoringTeams(): JsonResponse
    {
        try {
            // Get top teams by goals_for from standings
            $topTeams = Standing::select('team_id', DB::raw('SUM(goals_for) as total_goals'))
                ->groupBy('team_id')
                ->orderByDesc('total_goals')
                ->limit(5)
                ->get()
                ->map(function ($item) {
                    return [
                        'team_id' => $item->team_id,
                        'total_goals' => (int) $item->total_goals,
                    ];
                })
                ->toArray();

            return ApiResponse::success($topTeams, 'Top scoring teams retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error getting top scoring teams: ' . $e->getMessage());
            return ApiResponse::serverError('Failed to retrieve top scoring teams', $e);
        }
    }
}
