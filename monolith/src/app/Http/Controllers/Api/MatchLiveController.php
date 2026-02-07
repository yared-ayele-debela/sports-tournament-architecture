<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MatchLiveController extends Controller
{
    /**
     * Server-Sent Events (SSE) endpoint for real-time match updates
     * Optimized for multiple concurrent users with Redis-based shared state
     */
    public function stream(int $matchId): StreamedResponse
    {
        // Disable time limit for long-running SSE connection
        set_time_limit(0);
        ignore_user_abort(false);

        return response()->stream(function () use ($matchId) {
            // Set execution time limit for this connection
            set_time_limit(0);

            $connectionTime = time();
            $maxConnectionTime = config('sse.max_connection_time', 300); // 5 minutes max
            $lastDataHash = null;
            $heartbeatInterval = config('sse.heartbeat_interval', 30); // 30 seconds
            $lastHeartbeat = time();
            $updateInterval = config('sse.update_interval', 5); // 5 seconds
            $lastUpdate = time();

            // Verify match exists once at start
            try {
                $match = MatchModel::find($matchId);
                if (!$match) {
                    echo "data: " . json_encode([
                        'type' => 'error',
                        'message' => 'Match not found',
                    ]) . "\n\n";
                    ob_flush();
                    flush();
                    return;
                }
            } catch (\Exception $e) {
                echo "data: " . json_encode([
                    'type' => 'error',
                    'message' => 'Error loading match',
                ]) . "\n\n";
                ob_flush();
                flush();
                return;
            }

            // Send initial connection message
            try {
                echo "data: " . json_encode([
                    'type' => 'connected',
                    'match_id' => $matchId,
                    'timestamp' => now()->toIso8601String(),
                ]) . "\n\n";
                ob_flush();
                flush();
            } catch (\Exception $e) {
                return; // Client disconnected
            }

            $iterations = 0;
            $maxIterations = config('sse.max_iterations', 3600); // Safety limit

            while (true) {
                $iterations++;

                // Safety check to prevent infinite loops
                if ($iterations > $maxIterations) {
                    break;
                }

                // Check connection timeout
                if (time() - $connectionTime > $maxConnectionTime) {
                    try {
                        echo "data: " . json_encode([
                            'type' => 'timeout',
                            'message' => 'Connection timeout',
                        ]) . "\n\n";
                        ob_flush();
                        flush();
                    } catch (\Exception $e) {
                        // Client disconnected
                    }
                    break;
                }

                // Check if client disconnected (connection closed)
                if (connection_aborted()) {
                    break;
                }

                $now = time();

                // Only check for updates at intervals (not every loop)
                if ($now - $lastUpdate >= $updateInterval) {
                    $lastUpdate = $now;

                    try {
                        // Get cached data (shared across all connections)
                        $cacheKey = "match_live_{$matchId}";
                        $matchData = null;

                        // Try Redis first for better performance
                        if (config('cache.default') === 'redis') {
                            try {
                                if (Redis::connection()->ping()) {
                                    $cached = Redis::get($cacheKey);
                                    if ($cached) {
                                        $matchData = json_decode($cached, true);
                                    }
                                }
                            } catch (\Exception $e) {
                                // Redis connection failed, fallback to Cache
                            }
                        }

                        if (!$matchData) {
                            // Fallback to Cache facade
                            $matchData = Cache::get($cacheKey);
                        }

                        // Only fetch from DB if cache is empty (first connection or cache expired)
                        if (!$matchData) {
                            $match = MatchModel::find($matchId);
                            if (!$match) {
                                echo "data: " . json_encode([
                                    'type' => 'error',
                                    'message' => 'Match not found',
                                ]) . "\n\n";
                                ob_flush();
                                flush();
                                break;
                            }

                            $matchData = $this->getMatchData($match);
                            // Cache for 5 seconds (shared across all connections)
                            if (config('cache.default') === 'redis') {
                                try {
                                    if (Redis::connection()->ping()) {
                                        Redis::setex($cacheKey, 5, json_encode($matchData));
                                    }
                                } catch (\Exception $e) {
                                    Cache::put($cacheKey, $matchData, 5);
                                }
                            } else {
                                Cache::put($cacheKey, $matchData, 5);
                            }
                        }

                        // Only send update if data changed (reduce unnecessary data transfer)
                        if ($matchData) {
                            $currentHash = md5(json_encode($matchData));
                            if ($currentHash !== $lastDataHash) {
                                $lastDataHash = $currentHash;

                                echo "data: " . json_encode([
                                    'type' => 'update',
                                    'data' => $matchData,
                                    'timestamp' => now()->toIso8601String(),
                                ]) . "\n\n";
                                ob_flush();
                                flush();
                            }
                        }
                    } catch (\Exception $e) {
                        // Log error but continue
                        Log::error('SSE stream error: ' . $e->getMessage());
                        // Send error to client
                        try {
                            echo "data: " . json_encode([
                                'type' => 'error',
                                'message' => 'Update failed',
                            ]) . "\n\n";
                            ob_flush();
                            flush();
                        } catch (\Exception $e2) {
                            break; // Client disconnected
                        }
                    }
                }

                // Send heartbeat less frequently (every 30 seconds instead of every 2)
                if ($now - $lastHeartbeat >= $heartbeatInterval) {
                    $lastHeartbeat = $now;
                    try {
                        echo "data: " . json_encode([
                            'type' => 'heartbeat',
                            'timestamp' => now()->toIso8601String(),
                        ]) . "\n\n";
                        ob_flush();
                        flush();
                    } catch (\Exception $e) {
                        break; // Client disconnected
                    }
                }

                // Sleep to reduce CPU usage
                usleep(config('sse.sleep_interval', 1000000)); // 1 second
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Get match data for streaming
     */
    private function getMatchData(MatchModel $match): array
    {
        $match->load(['homeTeam', 'awayTeam', 'venue', 'tournament', 'matchEvents.player', 'matchEvents.team']);

        $events = $match->matchEvents->sortBy([
            ['minute', 'asc'],
            ['created_at', 'asc'],
        ]);

        $homeEvents = $events->where('team_id', $match->home_team_id);
        $awayEvents = $events->where('team_id', $match->away_team_id);

        // dd($events);

        return [
            'match' => [
                'id' => $match->id,
                'status' => $match->status,
                'home_score' => $match->home_score ?? 0,
                'away_score' => $match->away_score ?? 0,
                'current_minute' => $match->current_minute,
                'match_date' => $match->match_date->toIso8601String(),
            ],
            'teams' => [
                'home' => [
                    'id' => $match->homeTeam->id,
                    'name' => $match->homeTeam->name,
                    'logo' => $match->homeTeam->logo ? asset('storage/' . $match->homeTeam->logo) : null,
                ],
                'away' => [
                    'id' => $match->awayTeam->id,
                    'name' => $match->awayTeam->name,
                    'logo' => $match->awayTeam->logo ? asset('storage/' . $match->awayTeam->logo) : null,
                ],
            ],
            'stats' => [
                'home' => [
                    'goals' => $homeEvents->where('event_type', 'goal')->count(),
                    'yellow_cards' => $homeEvents->where('event_type', 'yellow_card')->count(),
                    'red_cards' => $homeEvents->where('event_type', 'red_card')->count(),
                    'substitutions' => $homeEvents->where('event_type', 'substitution')->count(),
                ],
                'away' => [
                    'goals' => $awayEvents->where('event_type', 'goal')->count(),
                    'yellow_cards' => $awayEvents->where('event_type', 'yellow_card')->count(),
                    'red_cards' => $awayEvents->where('event_type', 'red_card')->count(),
                    'substitutions' => $awayEvents->where('event_type', 'substitution')->count(),
                ],
            ],
            'events' => $events->map(function ($event) {
                return [
                    'id' => $event->id,
                    'minute' => $event->minute,
                    'event_type' => $event->event_type,
                    'description' => $event->description,
                    'player' => $event->player ? [
                        'id' => $event->player->id,
                        'name' => $event->player->name,
                    ] : null,
                    'team' => [
                        'id' => $event->team->id,
                        'name' => $event->team->name,
                    ],
                    'created_at' => $event->created_at->toIso8601String(),
                ];
            })->values(),
        ];
    }
}
