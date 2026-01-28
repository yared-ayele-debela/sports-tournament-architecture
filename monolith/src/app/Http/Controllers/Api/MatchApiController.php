<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchModel;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MatchApiController extends Controller
{
    /**
     * List matches with filters: tournament, team, status, date range.
     */
    public function index(Request $request): JsonResponse
    {
        $query = MatchModel::with(['homeTeam', 'awayTeam', 'venue', 'tournament'])
            ->orderBy('match_date', 'desc');

        if ($request->filled('tournament')) {
            $query->where('tournament_id', $request->tournament);
        }

        if ($request->filled('team')) {
            $query->where(function ($q) use ($request) {
                $q->where('home_team_id', $request->team)
                  ->orWhere('away_team_id', $request->team);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->where('match_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('match_date', '<=', $request->date_to);
        }

        $matches = $query->paginate(20);

        return response()->json($matches);
    }

    /**
     * Show a single match with events and derived stats.
     */
    public function show(int $id): JsonResponse
    {
        $match = MatchModel::with([
                'homeTeam.players',
                'awayTeam.players',
                'venue',
                'tournament',
                'matchEvents.player',
                'matchEvents.team',
            ])
            ->findOrFail($id);

        $homeEvents = $match->matchEvents->where('team_id', $match->home_team_id);
        $awayEvents = $match->matchEvents->where('team_id', $match->away_team_id);

        $stats = [
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
        ];

        $headToHead = MatchModel::with(['homeTeam', 'awayTeam', 'tournament'])
            ->where(function ($query) use ($match) {
                $query->where(function ($q) use ($match) {
                    $q->where('home_team_id', $match->home_team_id)
                      ->where('away_team_id', $match->away_team_id);
                })->orWhere(function ($q) use ($match) {
                    $q->where('home_team_id', $match->away_team_id)
                      ->where('away_team_id', $match->home_team_id);
                });
            })
            ->where('id', '!=', $match->id)
            ->orderBy('match_date', 'desc')
            ->take(5)
            ->get();

        return response()->json([
            'match' => $match,
            'stats' => $stats,
            'head_to_head' => $headToHead,
        ]);
    }

    /**
     * Recent matches helper endpoint.
     */
    public function recent(): JsonResponse
    {
        $matches = MatchModel::with(['homeTeam', 'awayTeam', 'venue', 'tournament'])
            ->orderBy('match_date', 'desc')
            ->take(10)
            ->get();

        return response()->json($matches);
    }
}

