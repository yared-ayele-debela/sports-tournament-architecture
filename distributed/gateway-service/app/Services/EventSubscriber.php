<?php

namespace App\Services;

use App\Services\Aggregators\TournamentAggregator;
use App\Services\Aggregators\MatchAggregator;
use App\Services\Aggregators\TeamAggregator;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class EventSubscriber
{
    protected TournamentAggregator $tournamentAggregator;
    protected MatchAggregator $matchAggregator;
    protected TeamAggregator $teamAggregator;

    public function __construct(
        TournamentAggregator $tournamentAggregator,
        MatchAggregator $matchAggregator,
        TeamAggregator $teamAggregator
    ) {
        $this->tournamentAggregator = $tournamentAggregator;
        $this->matchAggregator = $matchAggregator;
        $this->teamAggregator = $teamAggregator;
    }

    /**
     * Subscribe to match completed events
     */
    public function subscribeToMatchCompleted(): void
    {
        try {
            Redis::subscribe(['match.completed'], function ($message) {
                $this->handleMatchCompleted($message);
            });
        } catch (\Exception $e) {
            Log::error('Failed to subscribe to match.completed channel', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Subscribe to standings updated events
     */
    public function subscribeToStandingsUpdated(): void
    {
        try {
            Redis::subscribe(['standings.updated'], function ($message) {
                $this->handleStandingsUpdated($message);
            });
        } catch (\Exception $e) {
            Log::error('Failed to subscribe to standings.updated channel', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Subscribe to team updated events
     */
    public function subscribeToTeamUpdated(): void
    {
        try {
            Redis::subscribe(['team.updated'], function ($message) {
                $this->handleTeamUpdated($message);
            });
        } catch (\Exception $e) {
            Log::error('Failed to subscribe to team.updated channel', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Subscribe to tournament updated events
     */
    public function subscribeToTournamentUpdated(): void
    {
        try {
            Redis::subscribe(['tournament.updated'], function ($message) {
                $this->handleTournamentUpdated($message);
            });
        } catch (\Exception $e) {
            Log::error('Failed to subscribe to tournament.updated channel', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Subscribe to all channels
     */
    public function subscribeToAll(): void
    {
        $channels = [
            'match.completed',
            'standings.updated',
            'team.updated',
            'tournament.updated',
        ];

        try {
            Redis::subscribe($channels, function ($message, $channel) {
                switch ($channel) {
                    case 'match.completed':
                        $this->handleMatchCompleted($message);
                        break;
                    case 'standings.updated':
                        $this->handleStandingsUpdated($message);
                        break;
                    case 'team.updated':
                        $this->handleTeamUpdated($message);
                        break;
                    case 'tournament.updated':
                        $this->handleTournamentUpdated($message);
                        break;
                }
            });
        } catch (\Exception $e) {
            Log::error('Failed to subscribe to channels', [
                'channels' => $channels,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle match completed event
     */
    protected function handleMatchCompleted(string $message): void
    {
        try {
            $event = json_decode($message, true);
            
            if (!isset($event['tournament_id'])) {
                Log::warning('Invalid match.completed event format', ['message' => $message]);
                return;
            }

            $tournamentId = $event['tournament_id'];
            $matchId = $event['match_id'] ?? null;

            Log::info('Processing match.completed event', [
                'tournament_id' => $tournamentId,
                'match_id' => $matchId,
            ]);

            // Invalidate tournament-related caches
            $this->tournamentAggregator->invalidateTournamentCache($tournamentId);
            
            // Invalidate match-specific caches
            if ($matchId) {
                $this->matchAggregator->invalidateMatchCache($matchId);
            }

            // Invalidate live matches cache
            $this->matchAggregator->invalidateLiveMatchesCache();

            // Invalidate home page cache
            $this->invalidateHomePageCache();

            Log::info('Cache invalidated for match.completed event', [
                'tournament_id' => $tournamentId,
                'match_id' => $matchId,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to handle match.completed event', [
                'message' => $message,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle standings updated event
     */
    protected function handleStandingsUpdated(string $message): void
    {
        try {
            $event = json_decode($message, true);
            
            if (!isset($event['tournament_id'])) {
                Log::warning('Invalid standings.updated event format', ['message' => $message]);
                return;
            }

            $tournamentId = $event['tournament_id'];

            Log::info('Processing standings.updated event', [
                'tournament_id' => $tournamentId,
            ]);

            // Invalidate tournament-related caches
            $this->tournamentAggregator->invalidateTournamentCache($tournamentId);

            // Invalidate standings-specific caches
            $this->invalidateStandingsCache($tournamentId);

            // Invalidate home page cache
            $this->invalidateHomePageCache();

            Log::info('Cache invalidated for standings.updated event', [
                'tournament_id' => $tournamentId,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to handle standings.updated event', [
                'message' => $message,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle team updated event
     */
    protected function handleTeamUpdated(string $message): void
    {
        try {
            $event = json_decode($message, true);
            
            if (!isset($event['team_id'])) {
                Log::warning('Invalid team.updated event format', ['message' => $message]);
                return;
            }

            $teamId = $event['team_id'];
            $tournamentId = $event['tournament_id'] ?? null;

            Log::info('Processing team.updated event', [
                'team_id' => $teamId,
                'tournament_id' => $tournamentId,
            ]);

            // Invalidate team-related caches
            $this->teamAggregator->invalidateTeamCache($teamId);

            // Invalidate tournament cache if tournament_id is provided
            if ($tournamentId) {
                $this->tournamentAggregator->invalidateTournamentCache($tournamentId);
            }

            // Invalidate search cache
            $this->invalidateSearchCache();

            Log::info('Cache invalidated for team.updated event', [
                'team_id' => $teamId,
                'tournament_id' => $tournamentId,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to handle team.updated event', [
                'message' => $message,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle tournament updated event
     */
    protected function handleTournamentUpdated(string $message): void
    {
        try {
            $event = json_decode($message, true);
            
            if (!isset($event['tournament_id'])) {
                Log::warning('Invalid tournament.updated event format', ['message' => $message]);
                return;
            }

            $tournamentId = $event['tournament_id'];

            Log::info('Processing tournament.updated event', [
                'tournament_id' => $tournamentId,
            ]);

            // Invalidate tournament-related caches
            $this->tournamentAggregator->invalidateTournamentCache($tournamentId);

            // Invalidate home page cache
            $this->invalidateHomePageCache();

            // Invalidate search cache
            $this->invalidateSearchCache();

            Log::info('Cache invalidated for tournament.updated event', [
                'tournament_id' => $tournamentId,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to handle tournament.updated event', [
                'message' => $message,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Invalidate home page cache
     */
    protected function invalidateHomePageCache(): void
    {
        \Illuminate\Support\Facades\Cache::forget('home_page_data');
        \Illuminate\Support\Facades\Cache::forget('home_stats');
        \Illuminate\Support\Facades\Cache::tags(['home'])->flush();
    }

    /**
     * Invalidate standings cache
     */
    protected function invalidateStandingsCache(int $tournamentId): void
    {
        \Illuminate\Support\Facades\Cache::forget("standings_public:{$tournamentId}");
        \Illuminate\Support\Facades\Cache::forget("standings_with_teams_public:{$tournamentId}");
        \Illuminate\Support\Facades\Cache::forget("tournament_statistics_public:{$tournamentId}");
        \Illuminate\Support\Facades\Cache::forget("top_scorers_public:{$tournamentId}:10");
        \Illuminate\Support\Facades\Cache::tags(['standings', "tournament_{$tournamentId}"])->flush();
    }

    /**
     * Invalidate search cache
     */
    protected function invalidateSearchCache(): void
    {
        \Illuminate\Support\Facades\Cache::tags(['search'])->flush();
        \Illuminate\Support\Facades\Cache::forget('popular_searches_public');
    }
}
