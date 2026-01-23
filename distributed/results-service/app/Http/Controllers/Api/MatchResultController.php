<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchResult;
use App\Services\Clients\MatchServiceClient;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MatchResultController extends Controller
{
    protected MatchServiceClient $matchService;

    public function __construct(MatchServiceClient $matchService)
    {
        $this->matchService = $matchService;
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
        ]);

        // Get match data from Match Service
        $matchData = $this->matchService->getMatch($matchId);

        if (!$matchData) {
            return ApiResponse::notFound('Match not found');
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

        return ApiResponse::success($result, 'Match result finalized successfully');
    }
}
