<?php

namespace App\Services\Aggregators;

use App\Services\Clients\MatchServiceClient;
use App\Services\Clients\TeamServiceClient;
use App\Services\Clients\TournamentServiceClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MatchAggregator
{
    protected MatchServiceClient $matchClient;
    protected TeamServiceClient $teamClient;
    protected TournamentServiceClient $tournamentClient;

    public function __construct(
        MatchServiceClient $matchClient,
        TeamServiceClient $teamClient,
        TournamentServiceClient $tournamentClient
    ) {
        $this->matchClient = $matchClient;
        $this->teamClient = $teamClient;
        $this->tournamentClient = $tournamentClient;
    }

    /**
     * Get comprehensive match details
     */
    public function getMatchDetails(int $matchId): array
    {
        try {
            // Fetch match basic info first to determine status
            $matchResponse = $this->matchClient->getMatch($matchId);
            if (!$matchResponse['success']) {
                return [
                    'success' => false,
                    'error' => $matchResponse['error'] ?? 'Failed to fetch match',
                ];
            }

            $match = $matchResponse['data'];
            $isLive = ($match['status'] ?? '') === 'in_progress';
            $isCompleted = ($match['status'] ?? '') === 'completed';

            // Determine cache TTL based on match status
            $cacheTtl = $isLive ? 30 : ($isCompleted ? 3600 : 600); // 30s for live, 1h for completed, 10m for others
            $cacheKey = "match_details:{$matchId}";

            return Cache::remember($cacheKey, $cacheTtl, function () use ($match, $matchId, $isLive, $isCompleted) {
                // Fetch home team details
                $homeTeamResponse = $this->teamClient->getTeam($match['home_team_id']);
                $homeTeam = $homeTeamResponse['success'] ? $homeTeamResponse['data'] : null;

                // Fetch away team details
                $awayTeamResponse = $this->teamClient->getTeam($match['away_team_id']);
                $awayTeam = $awayTeamResponse['success'] ? $awayTeamResponse['data'] : null;

                // Fetch venue details if available
                $venue = null;
                if (!empty($match['venue_id'])) {
                    $venueResponse = $this->tournamentClient->getVenue($match['venue_id']);
                    $venue = $venueResponse['success'] ? $venueResponse['data'] : null;
                }

                // Fetch match events
                $eventsResponse = $this->matchClient->getMatchEvents($matchId);
                $events = $eventsResponse['success'] ? $eventsResponse['data'] : [];

                // Fetch match lineups for completed/live matches
                $lineups = [];
                if ($isLive || $isCompleted) {
                    $lineupsResponse = $this->matchClient->getMatchLineups($matchId);
                    $lineups = $lineupsResponse['success'] ? $lineupsResponse['data'] : [];
                }

                // Fetch match statistics for completed matches
                $statistics = [];
                if ($isCompleted) {
                    $statsResponse = $this->matchClient->getMatchStatistics($matchId);
                    $statistics = $statsResponse['success'] ? $statsResponse['data'] : [];
                }

                return [
                    'success' => true,
                    'data' => [
                        'match' => $match,
                        'home_team' => $homeTeam,
                        'away_team' => $awayTeam,
                        'venue' => $venue,
                        'events' => $events,
                        'lineups' => $lineups,
                        'statistics' => $statistics,
                    ],
                ];
            });
        } catch (\Exception $e) {
            Log::error('Failed to aggregate match details', [
                'match_id' => $matchId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to aggregate match data',
            ];
        }
    }

    /**
     * Get all live matches with team details (no caching)
     */
    public function getLiveMatches(): array
    {
        try {
            // Fetch live matches
            $liveMatchesResponse = $this->matchClient->getLiveMatches();
            if (!$liveMatchesResponse['success']) {
                return [
                    'success' => false,
                    'error' => $liveMatchesResponse['error'] ?? 'Failed to fetch live matches',
                ];
            }

            $liveMatches = $liveMatchesResponse['data']['data'] ?? [];
            // Log::info(['Live matches retrieved successfully', $liveMatches]);
            $aggregatedMatches = [];

            foreach ($liveMatches as $match) {
                // Fetch team details for each live match
                $homeTeamResponse = $this->teamClient->getTeam($match['home_team_id']);
                $awayTeamResponse = $this->teamClient->getTeam($match['away_team_id']);

                // Fetch match events for live matches
                $eventsResponse = $this->matchClient->getMatchEvents($match['id']);

                $aggregatedMatches[] = [
                    'match' => $match,
                    'home_team' => $homeTeamResponse['success'] ? $homeTeamResponse['data'] : null,
                    'away_team' => $awayTeamResponse['success'] ? $awayTeamResponse['data'] : null,
                    'events' => $eventsResponse['success'] ? $eventsResponse['data'] : [],
                ];
            }

            return [
                'success' => true,
                'data' => $aggregatedMatches,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to aggregate live matches', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to aggregate live matches',
            ];
        }
    }

    /**
     * Get matches by date with team details
     */
    public function getMatchesByDate(string $date): array
    {
        $cacheKey = "matches_by_date:{$date}";

        return Cache::remember($cacheKey, 600, function () use ($date) { // 10 minutes
            try {
                // Fetch matches for the date
                $matchesResponse = $this->matchClient->getMatches([
                    'date' => $date
                ]);

                if (!$matchesResponse['success']) {
                    return [
                        'success' => false,
                        'error' => $matchesResponse['error'] ?? 'Failed to fetch matches',
                    ];
                }

                $matches = $matchesResponse['data'];
                $aggregatedMatches = [];

                foreach ($matches as $match) {
                    // Fetch team details
                    $homeTeamResponse = $this->teamClient->getTeam($match['home_team_id']);
                    $awayTeamResponse = $this->teamClient->getTeam($match['away_team_id']);

                    $aggregatedMatches[] = [
                        'match' => $match,
                        'home_team' => $homeTeamResponse['success'] ? $homeTeamResponse['data'] : null,
                        'away_team' => $awayTeamResponse['success'] ? $awayTeamResponse['data'] : null,
                    ];
                }

                return [
                    'success' => true,
                    'data' => $aggregatedMatches,
                ];
            } catch (\Exception $e) {
                Log::error('Failed to aggregate matches by date', [
                    'date' => $date,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to aggregate matches by date',
                ];
            }
        });
    }

    /**
     * Get tournament matches with team details
     */
    public function getTournamentMatches(int $tournamentId, array $filters = []): array
    {
        $cacheKey = "tournament_matches:{$tournamentId}:" . md5(serialize($filters));

        return Cache::remember($cacheKey, 300, function () use ($tournamentId, $filters) { // 5 minutes
            try {
                // Fetch tournament matches
                $matchesResponse = $this->matchClient->getTournamentMatches($tournamentId, $filters);

                if (!$matchesResponse['success']) {
                    return [
                        'success' => false,
                        'error' => $matchesResponse['error'] ?? 'Failed to fetch tournament matches',
                    ];
                }

                $matches = $matchesResponse['data'];
                $aggregatedMatches = [];

                foreach ($matches as $match) {
                    // Fetch team details
                    $homeTeamResponse = $this->teamClient->getTeam($match['home_team_id']);
                    $awayTeamResponse = $this->teamClient->getTeam($match['away_team_id']);

                    $aggregatedMatches[] = [
                        'match' => $match,
                        'home_team' => $homeTeamResponse['success'] ? $homeTeamResponse['data'] : null,
                        'away_team' => $awayTeamResponse['success'] ? $awayTeamResponse['data'] : null,
                    ];
                }

                return [
                    'success' => true,
                    'data' => $aggregatedMatches,
                ];
            } catch (\Exception $e) {
                Log::error('Failed to aggregate tournament matches', [
                    'tournament_id' => $tournamentId,
                    'error' => $e->getMessage(),
                ]);

                return [
                    'success' => false,
                    'error' => 'Failed to aggregate tournament matches',
                ];
            }
        });
    }

    /**
     * Invalidate match-related caches
     */
    public function invalidateMatchCache(int $matchId): void
    {
        Cache::forget("match_details:{$matchId}");
        Cache::tags(['matches'])->flush();
    }

    /**
     * Invalidate live matches cache (should be called frequently)
     */
    public function invalidateLiveMatchesCache(): void
    {
        // Live matches are not cached, but we can invalidate related caches
        Cache::tags(['live_matches'])->flush();
    }
}
