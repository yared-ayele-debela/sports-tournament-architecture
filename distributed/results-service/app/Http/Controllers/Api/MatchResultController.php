<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchResult;
use App\Services\Clients\MatchServiceClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MatchResultController extends Controller
{
    protected MatchServiceClient $matchService;

    public function __construct(MatchServiceClient $matchService)
    {
        $this->matchService = $matchService;
    }

    public function index(Request $request, int $tournamentId): JsonResponse
    {
        $query = MatchResult::where('tournament_id', $tournamentId)
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('completed_at', 'desc');

        if ($request->has('team_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('home_team_id', $request->team_id)
                  ->orWhere('away_team_id', $request->team_id);
            });
        }

        $results = $query->get();

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $result = MatchResult::with(['homeTeam', 'awayTeam'])->find($id);

        if (!$result) {
            return response()->json([
                'success' => false,
                'message' => 'Match result not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    public function finalize(Request $request, int $matchId): JsonResponse
    {
        // Validate request
        $validated = $request->validate([
            'home_score' => 'required|integer|min:0',
            'away_score' => 'required|integer|min:0',
            'completed_at' => 'required|date',
        ]);

        // Get match data from Match Service
        $matchData = $this->matchService->getMatch($matchId);

        if (!$matchData) {
            return response()->json([
                'success' => false,
                'message' => 'Match not found',
            ], 404);
        }

        // Create or update match result
        $result = MatchResult::updateOrCreate(
            ['match_id' => $matchId],
            array_merge($validated, [
                'tournament_id' => $matchData['tournament_id'],
                'home_team_id' => $matchData['home_team_id'],
                'away_team_id' => $matchData['away_team_id'],
            ])
        );

        return response()->json([
            'success' => true,
            'message' => 'Match result finalized successfully',
            'data' => $result,
        ]);
    }
}
