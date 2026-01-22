<?php

namespace App\Services\Aggregators;

use App\Services\Clients\TeamServiceClient;
use App\Services\Clients\MatchServiceClient;
use App\Services\Clients\ResultsServiceClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TeamAggregator
{
    protected TeamServiceClient $teamClient;
    protected MatchServiceClient $matchClient;
    protected ResultsServiceClient $resultsClient;

    public function __construct(
        TeamServiceClient $teamClient,
        MatchServiceClient $matchClient,
        ResultsServiceClient $resultsClient
    ) {
        $this->teamClient = $teamClient;
        $this->matchClient = $matchClient;
        $this->resultsClient = $resultsClient;
    }

    /**
     * Get comprehensive team profile
     */
    public function getTeamProfile(int $teamId): array
    {
        $cacheKey = "team_profile:{$teamId}";

        return Cache::remember($cacheKey, 900, function () use ($teamId) { // 15 minutes
            try {
                // Fetch team basic info
                $teamResponse = $this->teamClient->getTeam($teamId);
                if (!$teamResponse['success']) {
                    return [
                        'success' => false,
                        'error' => $teamResponse['error'] ?? 'Failed to fetch team',
                    ];
                }

                $team = $teamResponse['data'];

                // Fetch team players
                $playersResponse = $this->teamClient->getTeamPlayers($teamId);
                $players = $playersResponse['success'] ? $playersResponse['data'] : [];

                // Fetch team statistics
                $statisticsResponse = $this->teamClient->getTeamStatistics($teamId);
                $statistics = $statisticsResponse['success'] ? $statisticsResponse['data'] : [];

                // Fetch recent matches (last 10)
                $recentMatchesResponse = $this->matchClient->getTeamMatches($teamId, [
                    'limit' => 10,
                    'sort' => 'date',
                    'order' => 'desc'
                ]);
                $recentMatches = $recentMatchesResponse['success'] ? $recentMatchesResponse['data'] : [];

                // Fetch team form (last 5 matches with results)
                $formResponse = $this->resultsClient->getTeamForm($teamId, 5);
                $form = $formResponse['success'] ? $formResponse['data'] : [];

                // Fetch upcoming matches (next 5)
                $upcomingMatchesResponse = $this->matchClient->getUpcomingMatches([
                    'team_id' => $teamId,
                    'limit' => 5
                ]);
                $upcomingMatches = $upcomingMatchesResponse['success'] ? $upcomingMatchesResponse['data'] : [];

                // Get player statistics for key players
                $playerStats = [];
                foreach (array_slice($players, 0, 10) as $player) { // Limit to first 10 players
                    $playerStatsResponse = $this->teamClient->getPlayerStatistics($player['id']);
                    if ($playerStatsResponse['success']) {
                        $playerStats[] = array_merge($player, [
                            'statistics' => $playerStatsResponse['data']
                        ]);
                    }
                }

                return [
                    'success' => true,
                    'data' => [
                        'team' => $team,
                        'players' => $players,
                        'player_statistics' => $playerStats,
                        'statistics' => $statistics,
                        'recent_matches' => $recentMatches,
                        'form' => $form,
                        'upcoming_matches' => $upcomingMatches,
                    ],
                ];
            } catch (\Exception $e) {
                Log::error('Failed to aggregate team profile', [
                    'team_id' => $teamId,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to aggregate team data',
                ];
            }
        });
    }

    /**
     * Get team overview with key information
     */
    public function getTeamOverview(int $teamId, int $tournamentId = null): array
    {
        $cacheKey = "team_overview:{$teamId}:" . ($tournamentId ?? 'all');

        return Cache::remember($cacheKey, 600, function () use ($teamId, $tournamentId) { // 10 minutes
            try {
                // Fetch team basic info
                $teamResponse = $this->teamClient->getTeam($teamId);
                if (!$teamResponse['success']) {
                    return [
                        'success' => false,
                        'error' => $teamResponse['error'] ?? 'Failed to fetch team',
                    ];
                }

                $team = $teamResponse['data'];

                // Fetch team statistics (tournament-specific if provided)
                $statisticsResponse = $this->teamClient->getTeamStatistics($teamId, $tournamentId);
                $statistics = $statisticsResponse['success'] ? $statisticsResponse['data'] : [];

                // Fetch team form
                $formResponse = $this->resultsClient->getTeamForm($teamId, 5);
                $form = $formResponse['success'] ? $formResponse['data'] : [];

                // Fetch key players (top 5)
                $playersResponse = $this->teamClient->getTeamPlayers($teamId);
                $players = $playersResponse['success'] ? array_slice($playersResponse['data'], 0, 5) : [];

                return [
                    'success' => true,
                    'data' => [
                        'team' => $team,
                        'statistics' => $statistics,
                        'form' => $form,
                        'key_players' => $players,
                    ],
                ];
            } catch (\Exception $e) {
                Log::error('Failed to get team overview', [
                    'team_id' => $teamId,
                    'tournament_id' => $tournamentId,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to get team overview',
                ];
            }
        });
    }

    /**
     * Get team head-to-head record against another team
     */
    public function getHeadToHead(int $team1Id, int $team2Id, int $tournamentId = null): array
    {
        $cacheKey = "head_to_head:{$team1Id}:{$team2Id}:" . ($tournamentId ?? 'all');

        return Cache::remember($cacheKey, 1800, function () use ($team1Id, $team2Id, $tournamentId) { // 30 minutes
            try {
                // Fetch head-to-head statistics
                $h2hResponse = $this->resultsClient->getHeadToHead($team1Id, $team2Id, $tournamentId);
                if (!$h2hResponse['success']) {
                    return [
                        'success' => false,
                        'error' => $h2hResponse['error'] ?? 'Failed to fetch head-to-head data',
                    ];
                }

                // Fetch both teams' basic info
                $team1Response = $this->teamClient->getTeam($team1Id);
                $team2Response = $this->teamClient->getTeam($team2Id);

                // Fetch recent matches between these teams
                $recentMatchesResponse = $this->matchClient->getMatches([
                    'team1_id' => $team1Id,
                    'team2_id' => $team2Id,
                    'tournament_id' => $tournamentId,
                    'limit' => 5
                ]);
                $recentMatches = $recentMatchesResponse['success'] ? $recentMatchesResponse['data'] : [];

                return [
                    'success' => true,
                    'data' => [
                        'head_to_head' => $h2hResponse['data'],
                        'team1' => $team1Response['success'] ? $team1Response['data'] : null,
                        'team2' => $team2Response['success'] ? $team2Response['data'] : null,
                        'recent_matches' => $recentMatches,
                    ],
                ];
            } catch (\Exception $e) {
                Log::error('Failed to get head-to-head data', [
                    'team1_id' => $team1Id,
                    'team2_id' => $team2Id,
                    'tournament_id' => $tournamentId,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to get head-to-head data',
                ];
            }
        });
    }

    /**
     * Get team squad with player details
     */
    public function getTeamSquad(int $teamId): array
    {
        $cacheKey = "team_squad:{$teamId}";

        return Cache::remember($cacheKey, 1200, function () use ($teamId) { // 20 minutes
            try {
                // Fetch team basic info
                $teamResponse = $this->teamClient->getTeam($teamId);
                if (!$teamResponse['success']) {
                    return [
                        'success' => false,
                        'error' => $teamResponse['error'] ?? 'Failed to fetch team',
                    ];
                }

                // Fetch all players
                $playersResponse = $this->teamClient->getTeamPlayers($teamId);
                if (!$playersResponse['success']) {
                    return [
                        'success' => false,
                        'error' => $playersResponse['error'] ?? 'Failed to fetch players',
                    ];
                }

                $players = $playersResponse['data'];
                $detailedPlayers = [];

                // Get detailed statistics for each player
                foreach ($players as $player) {
                    try {
                        $playerStatsResponse = $this->teamClient->getPlayerStatistics($player['id']);
                        $detailedPlayers[] = array_merge($player, [
                            'statistics' => $playerStatsResponse['success'] ? $playerStatsResponse['data'] : []
                        ]);
                    } catch (\Exception $e) {
                        // If getPlayerStatistics fails, just add player without stats
                        $detailedPlayers[] = $player;
                    }
                }

                return [
                    'success' => true,
                    'data' => [
                        'team' => $teamResponse['data'],
                        'players' => $detailedPlayers,
                    ],
                ];
            } catch (\Exception $e) {
                Log::error('Failed to get team squad', [
                    'team_id' => $teamId,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to get team squad',
                ];
            }
        });
    }

    /**
     * Invalidate team-related caches
     */
    public function invalidateTeamCache(int $teamId): void
    {
        $patterns = [
            "team_profile:{$teamId}",
            "team_overview:{$teamId}:*",
            "team_squad:{$teamId}",
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                // For patterns with wildcards, we'd need a more sophisticated cache clearing
                // For now, we'll clear by tags
                continue;
            }
            Cache::forget($pattern);
        }

        // Clear by tags
        Cache::tags(['teams', "team_{$teamId}"])->flush();
    }
}
