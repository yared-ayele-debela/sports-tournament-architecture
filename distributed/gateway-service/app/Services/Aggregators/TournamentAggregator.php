<?php

namespace App\Services\Aggregators;

use App\Services\Clients\TournamentServiceClient;
use App\Services\Clients\MatchServiceClient;
use App\Services\Clients\ResultsServiceClient;
use App\Services\Clients\TeamServiceClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TournamentAggregator
{
    protected TournamentServiceClient $tournamentClient;
    protected MatchServiceClient $matchClient;
    protected ResultsServiceClient $resultsClient;
    protected TeamServiceClient $teamClient;

    public function __construct(
        TournamentServiceClient $tournamentClient,
        MatchServiceClient $matchClient,
        ResultsServiceClient $resultsClient,
        TeamServiceClient $teamClient
    ) {
        $this->tournamentClient = $tournamentClient;
        $this->matchClient = $matchClient;
        $this->resultsClient = $resultsClient;
        $this->teamClient = $teamClient;
    }

    /**
     * Get comprehensive tournament details
     */
    public function getTournamentDetails(int $tournamentId): array
    {
        $cacheKey = "tournament_details:{$tournamentId}";

        return Cache::remember($cacheKey, 600, function () use ($tournamentId) { // 10 minutes
            try {
                // Fetch tournament basic info
                $tournamentResponse = $this->tournamentClient->getTournament($tournamentId);
                if (!$tournamentResponse['success']) {
                    return [
                        'success' => false,
                        'error' => $tournamentResponse['error'] ?? 'Failed to fetch tournament',
                    ];
                }

                $tournament = $tournamentResponse['data'];

                // Fetch teams
                $teamsResponse = $this->tournamentClient->getTournamentTeams($tournamentId);
                $teams = $teamsResponse['success'] ? $teamsResponse['data'] : [];

                // Fetch standings
                $standingsResponse = $this->resultsClient->getStandings($tournamentId);
                $standings = $standingsResponse['success'] ? $standingsResponse['data'] : [];

                // Fetch upcoming matches (next 5)
                $upcomingMatchesResponse = $this->matchClient->getUpcomingMatches([
                    'tournament_id' => $tournamentId,
                    'limit' => 5
                ]);
                $upcomingMatches = $upcomingMatchesResponse['success'] ? $upcomingMatchesResponse['data'] : [];

                return [
                    'success' => true,
                    'data' => [
                        'tournament' => $tournament,
                        'teams' => $teams,
                        'standings' => $standings,
                        'upcoming_matches' => $upcomingMatches,
                    ],
                ];
            } catch (\Exception $e) {
                Log::error('Failed to aggregate tournament details', [
                    'tournament_id' => $tournamentId,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to aggregate tournament data',
                ];
            }
        });
    }

    /**
     * Get featured tournaments with top teams
     */
    public function getFeaturedTournaments(): array
    {
        return Cache::remember('featured_tournaments', 300, function () { // 5 minutes
            try {
                // Fetch ongoing tournaments
                $tournamentsResponse = $this->tournamentClient->getTournaments([
                    'status' => 'ongoing',
                    'limit' => 10
                ]);

                if (!$tournamentsResponse['success']) {
                    return [
                        'success' => false,
                        'error' => $tournamentsResponse['error'] ?? 'Failed to fetch tournaments',
                    ];
                }

                $tournaments = $tournamentsResponse['data'];
                $featuredTournaments = [];

                foreach ($tournaments as $tournament) {
                    // Get standings for top 4 teams
                    $standingsResponse = $this->resultsClient->getStandings($tournament['id']);
                    $standings = $standingsResponse['success'] ? $standingsResponse['data'] : [];

                    // Get top 4 teams from standings
                    $topTeams = array_slice($standings, 0, 4);

                    // Fetch team details for top teams
                    $teamDetails = [];
                    foreach ($topTeams as $standing) {
                        $teamResponse = $this->teamClient->getTeam($standing['team_id']);
                        if ($teamResponse['success']) {
                            $teamDetails[] = array_merge($teamResponse['data'], [
                                'position' => $standing['position'],
                                'points' => $standing['points'],
                                'played' => $standing['played'],
                                'goal_difference' => $standing['goal_difference'] ?? 0,
                            ]);
                        }
                    }

                    $featuredTournaments[] = [
                        'tournament' => $tournament,
                        'top_teams' => $teamDetails,
                    ];
                }

                return [
                    'success' => true,
                    'data' => $featuredTournaments,
                ];
            } catch (\Exception $e) {
                Log::error('Failed to aggregate featured tournaments', [
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to aggregate featured tournaments',
                ];
            }
        });
    }

    /**
     * Get tournament overview with recent activity
     */
    public function getTournamentOverview(int $tournamentId): array
    {
        $cacheKey = "tournament_overview:{$tournamentId}";

        return Cache::remember($cacheKey, 300, function () use ($tournamentId) { // 5 minutes
            try {
                // Get tournament details
                $tournamentResponse = $this->tournamentClient->getTournament($tournamentId);
                if (!$tournamentResponse['success']) {
                    return ['success' => false, 'error' => $tournamentResponse['error']];
                }

                // Get recent matches (last 5 completed)
                $recentMatchesResponse = $this->matchClient->getCompletedMatches([
                    'tournament_id' => $tournamentId,
                    'limit' => 5
                ]);
                $recentMatches = $recentMatchesResponse['success'] ? $recentMatchesResponse['data'] : [];

                // Get top scorers
                $topScorersResponse = $this->resultsClient->getTopScorers($tournamentId, 5);
                $topScorers = $topScorersResponse['success'] ? $topScorersResponse['data'] : [];

                return [
                    'success' => true,
                    'data' => [
                        'tournament' => $tournamentResponse['data'],
                        'recent_matches' => $recentMatches,
                        'top_scorers' => $topScorers,
                    ],
                ];
            } catch (\Exception $e) {
                Log::error('Failed to get tournament overview', [
                    'tournament_id' => $tournamentId,
                    'error' => $e->getMessage(),
                ]);

                return ['success' => false, 'error' => 'Failed to get tournament overview'];
            }
        });
    }

    /**
     * Invalidate tournament-related caches
     */
    public function invalidateTournamentCache(int $tournamentId): void
    {
        $patterns = [
            "tournament_details:{$tournamentId}",
            "tournament_overview:{$tournamentId}",
            'featured_tournaments',
        ];

        foreach ($patterns as $key) {
            Cache::forget($key);
        }

        // Also invalidate by tags if supported
        Cache::tags(['tournaments'])->flush();
    }
}
