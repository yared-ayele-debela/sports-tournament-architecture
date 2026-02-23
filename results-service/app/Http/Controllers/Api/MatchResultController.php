<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchResult;
use App\Services\Clients\MatchServiceClient;
use App\Services\StandingsCalculator;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MatchResultController extends Controller
{
    protected MatchServiceClient $matchService;
    protected StandingsCalculator $standingsCalculator;

    public function __construct(MatchServiceClient $matchService, StandingsCalculator $standingsCalculator)
    {
        $this->matchService = $matchService;
        $this->standingsCalculator = $standingsCalculator;
    }

    /**
     * List match results for a tournament.
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
    public function index(Request $request, int $tournamentId): JsonResponse
    {
        $query = MatchResult::where('tournament_id', $tournamentId)
            ->orderBy('completed_at', 'desc');

        if ($request->has('team_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('home_team_id', $request->team_id)
                  ->orWhere('away_team_id', $request->team_id);
            });
        }

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $results = $query->paginate($perPage);

        return ApiResponse::paginated($results, 'Match results retrieved successfully');
    }

    public function show(int $id): JsonResponse
    {
        $result = MatchResult::find($id);

        if (!$result) {
            return ApiResponse::notFound('Match result not found');
        }

        return ApiResponse::success($result);
    }

    public function finalize(Request $request, int $matchId): JsonResponse
    {
        // Validate request
        $validated = $request->validate([
            'home_score' => 'required|integer|min:0',
            'away_score' => 'required|integer|min:0',
            'completed_at' => 'required|date',
            'tournament_id' => 'sometimes|integer',
            'home_team_id' => 'sometimes|integer',
            'away_team_id' => 'sometimes|integer',
        ]);

        // Try to get match data from Match Service
        $matchData = $this->matchService->getMatch($matchId);

        if (!$matchData) {
            // If we can't get match data from service, use provided data or fail
            if (!isset($validated['tournament_id']) || !isset($validated['home_team_id']) || !isset($validated['away_team_id'])) {
                return ApiResponse::error('Match not found and required match data not provided', 404);
            }
            
            $matchData = [
                'tournament_id' => $validated['tournament_id'],
                'home_team_id' => $validated['home_team_id'],
                'away_team_id' => $validated['away_team_id'],
            ];
        } else {
            // Extract actual match data from API response
            if (isset($matchData['data'])) {
                $matchData = $matchData['data'];
            }
        }

        // Debug logging
        Log::info('Match data for finalize', [
            'match_id' => $matchId,
            'match_data' => $matchData,
            'validated' => $validated
        ]);

        // Create or update match result
        $result = MatchResult::updateOrCreate(
            ['match_id' => $matchId],
            [
                'tournament_id' => $matchData['tournament_id'],
                'home_team_id' => $matchData['home_team_id'],
                'away_team_id' => $matchData['away_team_id'],
                'home_score' => $validated['home_score'],
                'away_score' => $validated['away_score'],
                'completed_at' => $validated['completed_at'],
            ]
        );

        // Trigger standings and statistics update
        $this->standingsCalculator->updateStandingsFromMatch($result);

        return ApiResponse::success($result, 'Match result finalized successfully');
    }
}
