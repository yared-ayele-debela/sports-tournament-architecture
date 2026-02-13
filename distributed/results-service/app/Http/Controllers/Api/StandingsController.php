<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StandingsCalculator;
use App\Models\Standing;
use App\Exceptions\ServiceRequestException;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class StandingsController extends Controller
{
    protected StandingsCalculator $standingsCalculator;

    public function __construct(StandingsCalculator $standingsCalculator)
    {
        $this->standingsCalculator = $standingsCalculator;
    }

    /**
     * Get standings for a tournament.
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
    public function index(Request $request, int $tournamentId): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $paginator = Standing::where('tournament_id', $tournamentId)
            ->orderBy('points', 'desc')
            ->orderBy('goal_difference', 'desc')
            ->orderBy('goals_for', 'desc')
            ->paginate($perPage);

        $items = collect($paginator->items())
            ->map(function ($standing, $index) {
                $standing->goal_difference = $standing->goals_for - $standing->goals_against;
                
                // Try to get team, but handle gracefully if team doesn't exist
                try {
                    $standing->team = $standing->getTeam();
                } catch (ServiceRequestException $e) {
                    // If team not found (404), set team to null and log warning
                    if ($e->getHttpStatusCode() === 404) {
                        Log::warning('Team not found for standing', [
                            'team_id' => $standing->team_id,
                            'tournament_id' => $standing->tournament_id,
                            'standing_id' => $standing->id,
                        ]);
                        $standing->team = null;
                    } else {
                        // For other errors, re-throw
                        throw $e;
                    }
                } catch (\Exception $e) {
                    // For any other exceptions, log and set team to null
                    Log::error('Failed to fetch team for standing', [
                        'team_id' => $standing->team_id,
                        'tournament_id' => $standing->tournament_id,
                        'standing_id' => $standing->id,
                        'error' => $e->getMessage(),
                    ]);
                    $standing->team = null;
                }
                
                $standing->position = $index + 1;
                return $standing;
            })
            ->all();

        $paginator->setCollection(collect($items));

        return ApiResponse::paginated($paginator, 'Standings retrieved successfully');
    }

    public function recalculate(Request $request, int $tournamentId): JsonResponse
    {
        // Check if user has admin permissions (simplified for now)
        // In a real implementation, you'd check user roles/permissions
        
        $this->standingsCalculator->recalculateForTournament($tournamentId);

        return ApiResponse::success(null, 'Standings recalculated successfully');
    }
}
