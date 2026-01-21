<?php

namespace App\Services;

use App\Models\MatchResult;
use App\Services\Clients\MatchServiceClient;
use App\Services\StandingsCalculator;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class EventSubscriber
{
    protected MatchServiceClient $matchService;
    protected StandingsCalculator $standingsCalculator;

    public function __construct(
        MatchServiceClient $matchService,
        StandingsCalculator $standingsCalculator
    ) {
        $this->matchService = $matchService;
        $this->standingsCalculator = $standingsCalculator;
    }

    public function subscribeToMatchCompleted(): void
    {
        try {
            Redis::subscribe(['match.completed'], function ($message) {
                $this->handleMatchCompleted($message);
            });
        } catch (\Exception $e) {
            Log::error('Failed to subscribe to Redis channel', [
                'channel' => 'match.completed',
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function handleMatchCompleted(string $message): void
    {
        try {
            $event = json_decode($message, true);

            if (!isset($event['match_id'])) {
                Log::warning('Invalid match completed event format', ['event' => $event]);
                return;
            }

            // Call Match Service to get final match data
            $matchData = $this->matchService->getMatch($event['match_id']);

            if (!$matchData) {
                Log::error('Match not found in Match Service', ['match_id' => $event['match_id']]);
                return;
            }

            // Store data in match_results table
            $matchResult = MatchResult::create([
                'match_id' => $matchData['id'],
                'tournament_id' => $matchData['tournament_id'],
                'home_team_id' => $matchData['home_team_id'],
                'away_team_id' => $matchData['away_team_id'],
                'home_score' => $matchData['home_score'],
                'away_score' => $matchData['away_score'],
                'completed_at' => $matchData['completed_at'] ?? now(),
            ]);

            // Update standings
            $this->standingsCalculator->updateStandingsFromMatch($matchResult);

            Log::info('Match result processed successfully', [
                'match_id' => $event['match_id'],
                'tournament_id' => $matchData['tournament_id'],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to handle match completed event', [
                'error' => $e->getMessage(),
                'message' => $message,
            ]);
        }
    }
}
