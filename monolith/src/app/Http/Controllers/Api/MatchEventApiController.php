<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use App\Models\MatchEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class MatchEventApiController extends Controller
{
    /**
     * Get all events for a match (cached for performance)
     */
    public function index(int $matchId): JsonResponse
    {
        $cacheKey = "match_events_{$matchId}";

        $events = Cache::remember($cacheKey, 30, function () use ($matchId) {
            return MatchEvent::where('match_id', $matchId)
                ->with(['player', 'team'])
                ->orderBy('minute', 'asc')
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($event) {
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
                });
        });

        return response()->json([
            'success' => true,
            'match_id' => $matchId,
            'events' => $events,
            'count' => $events->count(),
        ]);
    }

    /**
     * Get match live data (score, status, current minute, events)
     * Optimized with Redis caching for high-traffic scenarios
     */
    public function live(int $matchId): JsonResponse
    {
        $cacheKey = "match_live_{$matchId}";

        // Try Redis first (faster than Cache facade for high-frequency access)
        $data = null;
        if (config('cache.default') === 'redis' && Redis::connection()->ping()) {
            $cached = Redis::get($cacheKey);
            if ($cached) {
                $data = json_decode($cached, true);
            }
        } else {
            // Fallback to Cache facade (works with any cache driver)
            $data = Cache::get($cacheKey);
        }

        if (!$data) {
            // Only query DB if cache is empty
            $match = MatchModel::with(['homeTeam', 'awayTeam', 'venue', 'tournament'])
                ->findOrFail($matchId);

            $events = MatchEvent::where('match_id', $matchId)
                ->with(['player', 'team'])
                ->orderBy('minute', 'asc')
                ->orderBy('created_at', 'asc')
                ->get();

            $homeEvents = $events->where('team_id', $match->home_team_id);
            $awayEvents = $events->where('team_id', $match->away_team_id);

            $data = [
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
                }),
                'last_updated' => now()->toIso8601String(),
            ];

            // Cache for 5 seconds (shared across all requests)
            if (config('cache.default') === 'redis' && Redis::connection()->ping()) {
                // Use Redis SETEX for atomic set with expiration
                Redis::setex($cacheKey, 5, json_encode($data));
            } else {
                // Fallback to Cache facade
                Cache::put($cacheKey, $data, 5);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Get latest events for a match (for polling)
     * Optimized with caching and incremental updates
     */
    public function latest(int $matchId, Request $request): JsonResponse
    {
        $since = $request->input('since');
        $cacheKey = "match_events_latest_{$matchId}_" . ($since ? md5($since) : 'all');

        $events = Cache::remember($cacheKey, 5, function () use ($matchId, $since) {
            $query = MatchEvent::where('match_id', $matchId)
                ->with(['player', 'team']);

            if ($since) {
                $query->where('created_at', '>', $since);
            }

            return $query->orderBy('created_at', 'desc')
                ->take(10)
                ->get()
                ->map(function ($event) {
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
                });
        });

        return response()->json([
            'success' => true,
            'events' => $events,
            'count' => $events->count(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
