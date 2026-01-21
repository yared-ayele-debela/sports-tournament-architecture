<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StandingsCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StandingsController extends Controller
{
    protected StandingsCalculator $standingsCalculator;

    public function __construct(StandingsCalculator $standingsCalculator)
    {
        $this->standingsCalculator = $standingsCalculator;
    }

    public function index(Request $request, int $tournamentId): JsonResponse
    {
        $standings = $this->standingsCalculator->getTournamentStandings($tournamentId);

        return response()->json([
            'success' => true,
            'data' => $standings,
        ]);
    }

    public function recalculate(Request $request, int $tournamentId): JsonResponse
    {
        // Check if user has admin permissions (simplified for now)
        // In a real implementation, you'd check user roles/permissions
        
        $this->standingsCalculator->recalculateForTournament($tournamentId);

        return response()->json([
            'success' => true,
            'message' => 'Standings recalculated successfully',
        ]);
    }
}
