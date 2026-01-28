<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TournamentApiController extends Controller
{
    /**
     * List tournaments with optional filters (sport, status).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Tournament::with(['sport', 'teams'])
            ->orderBy('start_date', 'desc');

        if ($request->filled('sport')) {
            $query->whereHas('sport', function ($q) use ($request) {
                $q->where('name', $request->sport);
            });
        }

        if ($request->filled('status')) {
            switch ($request->status) {
                case 'active':
                    $query->where('start_date', '<=', now())
                          ->where('end_date', '>=', now());
                    break;
                case 'upcoming':
                    $query->where('start_date', '>', now());
                    break;
                case 'completed':
                    $query->where('end_date', '<', now());
                    break;
            }
        }

        $tournaments = $query->paginate(12);

        return response()->json($tournaments);
    }

    /**
     * Show a single tournament with related data.
     */
    public function show(int $id): JsonResponse
    {
        $tournament = Tournament::with([
                'sport',
                'teams.players',
                'matches.homeTeam',
                'matches.awayTeam',
                'matches.venue',
                'standings.team',
            ])
            ->findOrFail($id);

        return response()->json($tournament);
    }

    /**
     * Teams in a tournament.
     */
    public function teams(int $id): JsonResponse
    {
        $tournament = Tournament::findOrFail($id);

        $teams = $tournament->teams()
            ->withCount('players')
            ->orderBy('name')
            ->get();

        return response()->json([
            'tournament' => $tournament,
            'teams' => $teams,
        ]);
    }

    /**
     * Matches in a tournament.
     */
    public function matches(Request $request, int $id): JsonResponse
    {
        $tournament = Tournament::findOrFail($id);

        $query = $tournament->matches()
            ->with(['homeTeam', 'awayTeam', 'venue']);

        if ($request->filled('round')) {
            $query->where('round_number', $request->round);
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

        $matches = $query->orderBy('match_date', 'desc')->paginate(15);

        return response()->json([
            'tournament' => $tournament,
            'matches' => $matches,
        ]);
    }

    /**
     * Current standings in a tournament.
     */
    public function standings(int $id): JsonResponse
    {
        $tournament = Tournament::findOrFail($id);

        $standings = $tournament->standings()
            ->with('team')
            ->orderBy('position')
            ->get();

        return response()->json([
            'tournament' => $tournament,
            'standings' => $standings,
        ]);
    }
}

