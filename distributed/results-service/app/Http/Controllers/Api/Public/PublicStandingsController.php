<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Api\Public\PublicApiController;
use App\Models\Standing;
use App\Models\MatchResult;
use App\Services\PublicCacheService;
use App\Services\Clients\TeamServiceClient;
use App\Services\Clients\MatchServiceClient;
use App\Services\Clients\TournamentServiceClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Throwable;
use Carbon\Carbon;

class PublicStandingsController extends PublicApiController
{
    protected PublicCacheService $cacheService;
    protected TeamServiceClient $teamServiceClient;
    protected MatchServiceClient $matchServiceClient;
    protected TournamentServiceClient $tournamentServiceClient;

    public function __construct(
        PublicCacheService $cacheService,
        TeamServiceClient $teamServiceClient,
        MatchServiceClient $matchServiceClient,
        TournamentServiceClient $tournamentServiceClient
    ) {
        parent::__construct();
        $this->cacheService = $cacheService;
        $this->teamServiceClient = $teamServiceClient;
        $this->matchServiceClient = $matchServiceClient;
        $this->tournamentServiceClient = $tournamentServiceClient;
        $this->defaultCacheTags = ['public-api', 'standings'];
    }

    /**
     * GET /api/public/tournaments/{tournamentId}/standings
     * Full tournament standings table
     */
    public function standings(Request $request, int $tournamentId): JsonResponse
    {
        try {
            // Validate tournament exists
            $tournament = $this->tournamentServiceClient->getPublicTournament($tournamentId);
            if (!$tournament) {
                return $this->errorResponse('Tournament not found', 404, null, 'TOURNAMENT_NOT_FOUND');
            }

            $cacheKey = $this->cacheService->generateKey("tournament:{$tournamentId}:standings");
            $tags = ["public:tournament:{$tournamentId}:standings"];
            $ttl = 300; // 5 minutes

            $data = $this->cacheService->remember($cacheKey, $ttl, function () use ($tournamentId) {
                return $this->fetchStandings($tournamentId);
            }, $tags, 'live');

            return $this->successResponse($data, 'Tournament standings retrieved successfully', 200, $ttl);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve tournament standings', [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Failed to retrieve tournament standings', 500);
        }
    }

    /**
     * GET /api/public/teams/{teamId}/standing
     * Single team's standing in their tournament
     */
    public function teamStanding(Request $request, int $teamId): JsonResponse
    {
        try {
            $cacheKey = $this->cacheService->generateKey("team:{$teamId}:standing");
            $tags = ["public:team:{$teamId}:standing"];
            $ttl = 300; // 5 minutes

            $data = $this->cacheService->remember($cacheKey, $ttl, function () use ($teamId) {
                return $this->fetchTeamStanding($teamId);
            }, $tags, 'live');

            if (!$data) {
                return $this->errorResponse('Team standing not found', 404, null, 'TEAM_STANDING_NOT_FOUND');
            }

            return $this->successResponse($data, 'Team standing retrieved successfully', 200, $ttl);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve team standing', [
                'team_id' => $teamId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Failed to retrieve team standing', 500);
        }
    }

    /**
     * GET /api/public/tournaments/{tournamentId}/statistics
     * Tournament statistics
     */
    public function statistics(Request $request, int $tournamentId): JsonResponse
    {
        try {
            // Validate tournament exists
            $tournament = $this->tournamentServiceClient->getPublicTournament($tournamentId);
            if (!$tournament) {
                return $this->errorResponse('Tournament not found', 404, null, 'TOURNAMENT_NOT_FOUND');
            }

            $cacheKey = $this->cacheService->generateKey("tournament:{$tournamentId}:statistics");
            $tags = ["public:tournament:{$tournamentId}:statistics"];
            $ttl = 600; // 10 minutes

            $data = $this->cacheService->remember($cacheKey, $ttl, function () use ($tournamentId) {
                return $this->fetchTournamentStatistics($tournamentId);
            }, $tags, 'live');

            return $this->successResponse($data, 'Tournament statistics retrieved successfully', 200, $ttl);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve tournament statistics', [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Failed to retrieve tournament statistics', 500);
        }
    }

    /**
     * GET /api/public/tournaments/{tournamentId}/top-scorers
     * Top 10 goal scorers in tournament
     */
    public function topScorers(Request $request, int $tournamentId): JsonResponse
    {
        try {
            // Validate tournament exists
            $tournament = $this->tournamentServiceClient->getPublicTournament($tournamentId);
            if (!$tournament) {
                return $this->errorResponse('Tournament not found', 404, null, 'TOURNAMENT_NOT_FOUND');
            }

            $cacheKey = $this->cacheService->generateKey("tournament:{$tournamentId}:top-scorers");
            $tags = ["public:tournament:{$tournamentId}:scorers"];
            $ttl = 900; // 15 minutes

            $data = $this->cacheService->remember($cacheKey, $ttl, function () use ($tournamentId) {
                return $this->fetchTopScorers($tournamentId);
            }, $tags, 'live');

            return $this->successResponse($data, 'Top scorers retrieved successfully', 200, $ttl);
        } catch (Throwable $e) {
            Log::error('Failed to retrieve top scorers', [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->errorResponse('Failed to retrieve top scorers', 500);
        }
    }

    /**
     * Fetch tournament standings
     */
    protected function fetchStandings(int $tournamentId): array
    {
        $standings = Standing::where('tournament_id', $tournamentId)
            ->orderBy('points', 'desc')
            ->orderBy('goal_difference', 'desc')
            ->orderBy('goals_for', 'desc')
            ->get();

        $formattedStandings = [];
        $position = 1;

        foreach ($standings as $standing) {
            $team = $this->teamServiceClient->getPublicTeam($standing->team_id);

            $formattedStandings[] = [
                'position' => $position++,
                'team' => $team ? [
                    'id' => $team['id'] ?? null,
                    'name' => $team['name'] ?? null,
                ] : null,
                'played' => $standing->played,
                'won' => $standing->won,
                'drawn' => $standing->drawn,
                'lost' => $standing->lost,
                'goals_for' => $standing->goals_for,
                'goals_against' => $standing->goals_against,
                'goal_difference' => $standing->goal_difference ?? ($standing->goals_for - $standing->goals_against),
                'points' => $standing->points,
            ];
        }

        return [
            'standings' => $formattedStandings,
            'count' => count($formattedStandings),
        ];
    }

    /**
     * Fetch team standing
     */
    protected function fetchTeamStanding(int $teamId): ?array
    {
        $standing = Standing::where('team_id', $teamId)
            ->orderBy('points', 'desc')
            ->orderBy('goal_difference', 'desc')
            ->orderBy('goals_for', 'desc')
            ->first();

        if (!$standing) {
            return null;
        }

        // Get position in tournament by getting all standings and finding index
        $allStandings = Standing::where('tournament_id', $standing->tournament_id)
            ->orderBy('points', 'desc')
            ->orderBy('goal_difference', 'desc')
            ->orderBy('goals_for', 'desc')
            ->get();

        $position = 1;
        foreach ($allStandings as $s) {
            if ($s->team_id == $teamId) {
                break;
            }
            $position++;
        }

        // Get team info
        $team = $this->teamServiceClient->getPublicTeam($teamId);
        $tournament = $this->tournamentServiceClient->getPublicTournament($standing->tournament_id);

        // Get form (last 5 matches)
        $form = $this->getTeamForm($teamId, $standing->tournament_id);

        return [
            'team' => $team ? [
                'id' => $team['id'] ?? null,
                'name' => $team['name'] ?? null,
            ] : null,
            'tournament' => $tournament ? [
                'id' => $tournament['id'] ?? null,
                'name' => $tournament['name'] ?? null,
            ] : null,
            'position' => $position,
            'played' => $standing->played,
            'won' => $standing->won,
            'drawn' => $standing->drawn,
            'lost' => $standing->lost,
            'goals_for' => $standing->goals_for,
            'goals_against' => $standing->goals_against,
            'goal_difference' => $standing->goal_difference ?? ($standing->goals_for - $standing->goals_against),
            'points' => $standing->points,
            'form' => $form,
        ];
    }

    /**
     * Fetch tournament statistics
     */
    protected function fetchTournamentStatistics(int $tournamentId): array
    {
        $standings = Standing::where('tournament_id', $tournamentId)->get();
        $matchResults = MatchResult::where('tournament_id', $tournamentId)->get();

        // Calculate totals
        $totalMatches = $matchResults->count();
        $totalGoals = $matchResults->sum(function ($result) {
            return ($result->home_score ?? 0) + ($result->away_score ?? 0);
        });

        // Get top scorers (simplified - would need match events)
        $topScorers = $this->getTopScorers($tournamentId, 5);

        // Best defense (fewest goals conceded)
        $bestDefense = $standings->sortBy('goals_against')->first();
        $bestDefenseTeam = $bestDefense ? $this->teamServiceClient->getPublicTeam($bestDefense->team_id) : null;

        // Best attack (most goals scored)
        $bestAttack = $standings->sortByDesc('goals_for')->first();
        $bestAttackTeam = $bestAttack ? $this->teamServiceClient->getPublicTeam($bestAttack->team_id) : null;

        // Most cards (would need match events)
        $mostCards = null; // Placeholder - would need match events data

        // Clean sheets (would need match events or calculate from results)
        $cleanSheets = $this->calculateCleanSheets($tournamentId);

        return [
            'tournament_id' => $tournamentId,
            'total_matches' => $totalMatches,
            'total_goals' => $totalGoals,
            'average_goals_per_match' => $totalMatches > 0 ? round($totalGoals / $totalMatches, 2) : 0,
            'teams_participating' => $standings->count(),
            'top_scorers' => array_slice($topScorers, 0, 5),
            'best_defense' => $bestDefenseTeam ? [
                'team' => [
                    'id' => $bestDefenseTeam['id'] ?? null,
                    'name' => $bestDefenseTeam['name'] ?? null,
                ],
                'goals_conceded' => $bestDefense->goals_against,
            ] : null,
            'best_attack' => $bestAttackTeam ? [
                'team' => [
                    'id' => $bestAttackTeam['id'] ?? null,
                    'name' => $bestAttackTeam['name'] ?? null,
                ],
                'goals_scored' => $bestAttack->goals_for,
            ] : null,
            'clean_sheets' => $cleanSheets,
        ];
    }

    /**
     * Fetch top scorers
     */
    protected function fetchTopScorers(int $tournamentId): array
    {
        $topScorers = $this->getTopScorers($tournamentId, 10);

        return [
            'scorers' => $topScorers,
            'count' => count($topScorers),
        ];
    }

    /**
     * Get top scorers from match events
     */
    protected function getTopScorers(int $tournamentId, int $limit = 10): array
    {
        try {
            // Get all completed matches for this tournament
            $matchesData = $this->matchServiceClient->getPublicTournamentMatches($tournamentId, ['status' => 'completed']);

            if (!$matchesData || !isset($matchesData['matches'])) {
                return [];
            }

            $matches = $matchesData['matches'];
            $goalScorers = [];

            // Aggregate goals from match events
            foreach ($matches as $match) {
                $matchId = $match['id'] ?? null;
                if (!$matchId) {
                    continue;
                }

                try {
                    $events = $this->matchServiceClient->getPublicMatchEvents($matchId);
                    if (!$events || !is_array($events)) {
                        continue;
                    }

                    foreach ($events as $event) {
                        if (($event['event_type'] ?? '') === 'goal' && isset($event['player'])) {
                            $playerId = $event['player']['id'] ?? null;
                            if ($playerId) {
                                if (!isset($goalScorers[$playerId])) {
                                    $goalScorers[$playerId] = [
                                        'player_id' => $playerId,
                                        'player_name' => $event['player']['full_name'] ?? 'Unknown',
                                        'team_id' => $event['team']['id'] ?? null,
                                        'team_name' => $event['team']['name'] ?? null,
                                        'goals' => 0,
                                    ];
                                }
                                $goalScorers[$playerId]['goals']++;
                            }
                        }
                    }
                } catch (Throwable $e) {
                    // Continue to next match if events fetch fails
                    Log::debug('Failed to get events for match', [
                        'match_id' => $matchId,
                        'error' => $e->getMessage(),
                    ]);
                    continue;
                }
            }

            // Sort by goals and get top N
            usort($goalScorers, function ($a, $b) {
                return $b['goals'] <=> $a['goals'];
            });

            return array_slice($goalScorers, 0, $limit);

        } catch (Throwable $e) {
            Log::warning('Failed to get top scorers from match events', [
                'tournament_id' => $tournamentId,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Get team form (last 5 matches: W/D/L)
     */
    protected function getTeamForm(int $teamId, int $tournamentId): string
    {
        $matchResults = MatchResult::where('tournament_id', $tournamentId)
            ->where(function ($query) use ($teamId) {
                $query->where('home_team_id', $teamId)
                    ->orWhere('away_team_id', $teamId);
            })
            ->orderBy('completed_at', 'desc')
            ->limit(5)
            ->get();

        $form = [];
        foreach ($matchResults as $result) {
            if ($result->home_team_id == $teamId) {
                if ($result->home_score > $result->away_score) {
                    $form[] = 'W';
                } elseif ($result->home_score < $result->away_score) {
                    $form[] = 'L';
                } else {
                    $form[] = 'D';
                }
            } else {
                if ($result->away_score > $result->home_score) {
                    $form[] = 'W';
                } elseif ($result->away_score < $result->home_score) {
                    $form[] = 'L';
                } else {
                    $form[] = 'D';
                }
            }
        }

        return implode('', array_reverse($form)); // Reverse to show oldest to newest
    }

    /**
     * Calculate clean sheets (teams with 0 goals conceded in a match)
     */
    protected function calculateCleanSheets(int $tournamentId): array
    {
        $matchResults = MatchResult::where('tournament_id', $tournamentId)->get();
        $cleanSheets = [];

        foreach ($matchResults as $result) {
            // Home team clean sheet
            if ($result->away_score == 0) {
                $teamId = $result->home_team_id;
                if (!isset($cleanSheets[$teamId])) {
                    $cleanSheets[$teamId] = 0;
                }
                $cleanSheets[$teamId]++;
            }

            // Away team clean sheet
            if ($result->home_score == 0) {
                $teamId = $result->away_team_id;
                if (!isset($cleanSheets[$teamId])) {
                    $cleanSheets[$teamId] = 0;
                }
                $cleanSheets[$teamId]++;
            }
        }

        // Sort by clean sheets and get top 5
        arsort($cleanSheets);
        $topCleanSheets = [];
        $count = 0;
        foreach ($cleanSheets as $teamId => $sheets) {
            if ($count >= 5) {
                break;
            }
            $team = $this->teamServiceClient->getPublicTeam($teamId);
            $topCleanSheets[] = [
                'team' => $team ? [
                    'id' => $team['id'] ?? null,
                    'name' => $team['name'] ?? null,
                ] : null,
                'clean_sheets' => $sheets,
            ];
            $count++;
        }

        return $topCleanSheets;
    }
}
