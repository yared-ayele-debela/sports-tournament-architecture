<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchGame;
use App\Services\MatchScheduler;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MatchController extends Controller
{
    protected MatchScheduler $matchScheduler;

    public function __construct(MatchScheduler $matchScheduler)
    {
        $this->matchScheduler = $matchScheduler;
    }

    public function index(Request $request): JsonResponse
    {
        $query = MatchGame::with(['matchEvents', 'matchReport']);

        // Filters
        if ($request->has('tournament_id')) {
            $query->where('tournament_id', $request->tournament_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('team_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('home_team_id', $request->team_id)
                  ->orWhere('away_team_id', $request->team_id);
            });
        }

        $matches = $query->orderBy('match_date')->paginate(20);

        return response()->json([
            'data' => $matches->items(),
            'meta' => [
                'current_page' => $matches->currentPage(),
                'last_page' => $matches->lastPage(),
                'per_page' => $matches->perPage(),
                'total' => $matches->total(),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tournament_id' => 'required|integer',
            'venue_id' => 'required|integer',
            'home_team_id' => 'required|integer',
            'away_team_id' => 'required|integer|different:home_team_id',
            'referee_id' => 'required|integer',
            'match_date' => 'required|date',
            'round_number' => 'required|integer|min:1',
        ]);

        $match = MatchGame::create($validated);

        return response()->json($match->load(['matchEvents', 'matchReport']), 201);
    }

    public function show(string $id): JsonResponse
    {
        $match = MatchGame::with(['matchEvents', 'matchReport'])
            ->findOrFail($id);

        // Load external data
        $match->home_team = $match->getHomeTeam();
        $match->away_team = $match->getAwayTeam();
        $match->tournament = $match->getTournament();
        $match->venue = $match->getVenue();

        return response()->json($match);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $match = MatchGame::findOrFail($id);

        $validated = $request->validate([
            'venue_id' => 'sometimes|integer',
            'referee_id' => 'sometimes|integer',
            'match_date' => 'sometimes|date',
            'round_number' => 'sometimes|integer|min:1',
            'home_score' => 'sometimes|integer|min:0',
            'away_score' => 'sometimes|integer|min:0',
            'current_minute' => 'sometimes|integer|min:0|max:120',
        ]);

        $match->update($validated);

        return response()->json($match->load(['matchEvents', 'matchReport']));
    }

    public function destroy(string $id): JsonResponse
    {
        $match = MatchGame::findOrFail($id);
        $match->delete();

        return response()->json(null, 204);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
            'current_minute' => 'sometimes|integer|min:0|max:120',
        ]);

        $match = MatchGame::findOrFail($id);
        $match->update($validated);

        return response()->json($match);
    }

    public function generateSchedule(string $tournamentId): JsonResponse
    {
        try {
            $schedule = $this->matchScheduler->generateRoundRobin((int)$tournamentId);
            return response()->json($schedule, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to generate schedule',
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
