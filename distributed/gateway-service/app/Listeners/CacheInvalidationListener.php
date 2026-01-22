<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;

class CacheInvalidationListener implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle($event): void
    {
        try {
            $channel = $event['channel'] ?? null;
            $data = $event['data'] ?? [];

            switch ($channel) {
                case 'match.completed':
                    $this->handleMatchCompleted($data);
                    break;
                case 'standings.updated':
                    $this->handleStandingsUpdated($data);
                    break;
                case 'team.updated':
                    $this->handleTeamUpdated($data);
                    break;
                case 'tournament.updated':
                    $this->handleTournamentUpdated($data);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Cache invalidation listener failed', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle match completed event
     */
    protected function handleMatchCompleted(array $data): void
    {
        $tournamentId = $data['tournament_id'] ?? null;
        $matchId = $data['match_id'] ?? null;

        if (!$tournamentId) {
            Log::warning('Missing tournament_id in match.completed event');
            return;
        }

        Log::info('Invalidating cache for match.completed', [
            'tournament_id' => $tournamentId,
            'match_id' => $matchId,
        ]);

        // Flush tournament-specific caches
        $this->flushTournamentCache($tournamentId);

        // Flush match-specific caches
        if ($matchId) {
            $this->flushMatchCache($matchId);
        }

        // Flush global caches
        $this->flushGlobalCaches();
    }

    /**
     * Handle standings updated event
     */
    protected function handleStandingsUpdated(array $data): void
    {
        $tournamentId = $data['tournament_id'] ?? null;

        if (!$tournamentId) {
            Log::warning('Missing tournament_id in standings.updated event');
            return;
        }

        Log::info('Invalidating cache for standings.updated', [
            'tournament_id' => $tournamentId,
        ]);

        // Flush tournament-specific caches
        $this->flushTournamentCache($tournamentId);

        // Flush standings-specific caches
        $this->flushStandingsCache($tournamentId);

        // Flush global caches
        $this->flushGlobalCaches();
    }

    /**
     * Handle team updated event
     */
    protected function handleTeamUpdated(array $data): void
    {
        $teamId = $data['team_id'] ?? null;
        $tournamentId = $data['tournament_id'] ?? null;

        if (!$teamId) {
            Log::warning('Missing team_id in team.updated event');
            return;
        }

        Log::info('Invalidating cache for team.updated', [
            'team_id' => $teamId,
            'tournament_id' => $tournamentId,
        ]);

        // Flush team-specific caches
        $this->flushTeamCache($teamId);

        // Flush tournament-specific caches if provided
        if ($tournamentId) {
            $this->flushTournamentCache($tournamentId);
        }

        // Flush search cache
        $this->flushSearchCache();
    }

    /**
     * Handle tournament updated event
     */
    protected function handleTournamentUpdated(array $data): void
    {
        $tournamentId = $data['tournament_id'] ?? null;

        if (!$tournamentId) {
            Log::warning('Missing tournament_id in tournament.updated event');
            return;
        }

        Log::info('Invalidating cache for tournament.updated', [
            'tournament_id' => $tournamentId,
        ]);

        // Flush tournament-specific caches
        $this->flushTournamentCache($tournamentId);

        // Flush global caches
        $this->flushGlobalCaches();

        // Flush search cache
        $this->flushSearchCache();
    }

    /**
     * Flush tournament-related caches
     */
    protected function flushTournamentCache(int $tournamentId): void
    {
        $patterns = [
            "tournament_details_public:{$tournamentId}",
            "tournament_overview_public:{$tournamentId}",
            "tournament_teams_public:{$tournamentId}",
            "tournament_matches_public:{$tournamentId}:*",
            "tournament_statistics_public:{$tournamentId}",
            "top_scorers_public:{$tournamentId}:*",
            "tournament_details:{$tournamentId}",
            "tournament_overview:{$tournamentId}",
            "tournaments_list:*",
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                // For patterns with wildcards, we'd need a more sophisticated cache clearing
                // For now, we'll use tags
                continue;
            }
            Cache::forget($pattern);
        }

        // Clear by tags
        Cache::tags(['tournaments', "tournament_{$tournamentId}"])->flush();
    }

    /**
     * Flush match-related caches
     */
    protected function flushMatchCache(int $matchId): void
    {
        $patterns = [
            "match_details_public:{$matchId}",
            "match_details:{$matchId}",
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }

        // Clear by tags
        Cache::tags(['matches', "match_{$matchId}"])->flush();
    }

    /**
     * Flush standings-related caches
     */
    protected function flushStandingsCache(int $tournamentId): void
    {
        $patterns = [
            "standings_public:{$tournamentId}",
            "standings_with_teams_public:{$tournamentId}",
            "tournament_statistics_public:{$tournamentId}",
            "top_scorers_public:{$tournamentId}:*",
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                continue;
            }
            Cache::forget($pattern);
        }

        // Clear by tags
        Cache::tags(['standings', "tournament_{$tournamentId}"])->flush();
    }

    /**
     * Flush team-related caches
     */
    protected function flushTeamCache(int $teamId): void
    {
        $patterns = [
            "team_profile_public:{$teamId}",
            "team_overview_public:{$teamId}:*",
            "team_squad_public:{$teamId}",
            "team_matches_public:{$teamId}:*",
            "team_statistics_public:{$teamId}:*",
            "team_profile:{$teamId}",
            "team_overview:{$teamId}:*",
            "team_squad:{$teamId}",
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                continue;
            }
            Cache::forget($pattern);
        }

        // Clear by tags
        Cache::tags(['teams', "team_{$teamId}"])->flush();
    }

    /**
     * Flush global caches
     */
    protected function flushGlobalCaches(): void
    {
        $patterns = [
            'home_page_data',
            'home_stats',
            'featured_tournaments',
            'matches_list_public:*',
            'upcoming_matches_public:*',
            'completed_matches_public:*',
            'live_matches_public', // Note: this should not be cached, but just in case
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                continue;
            }
            Cache::forget($pattern);
        }

        // Clear by tags
        Cache::tags(['home', 'matches', 'live_matches'])->flush();
    }

    /**
     * Flush search-related caches
     */
    protected function flushSearchCache(): void
    {
        $patterns = [
            'search_public:*',
            'search_suggestions_public:*',
            'popular_searches_public',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($pattern, '*')) {
                continue;
            }
            Cache::forget($pattern);
        }

        // Clear by tags
        Cache::tags(['search'])->flush();
    }
}
