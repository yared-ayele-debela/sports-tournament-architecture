<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MatchGame;
use App\Services\MatchScheduler;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MatchController extends Controller
{
    protected MatchScheduler $matchScheduler;

    public function __construct(MatchScheduler $matchScheduler)
    {
        $this->matchScheduler = $matchScheduler;
    }

    /**
     * Display a listing of matches.
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
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

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $matches = $query->orderBy('match_date')->paginate($perPage);

        return ApiResponse::paginated($matches, 'Matches retrieved successfully');
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

        return ApiResponse::created($match->load(['matchEvents', 'matchReport']));
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

        return ApiResponse::success($match);
    }

    public function publicShow(string $id): JsonResponse
    {
        $match = MatchGame::with(['matchEvents', 'matchReport'])
            ->findOrFail($id);

        // Load external data
        $match->home_team = $match->getHomeTeam();
        $match->away_team = $match->getAwayTeam();
        $match->tournament = $match->getTournament();
        $match->venue = $match->getVenue();

        return ApiResponse::success($match);
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

        return ApiResponse::success($match->load(['matchEvents', 'matchReport']));
    }

    public function destroy(string $id): JsonResponse
    {
        $match = MatchGame::findOrFail($id);
        $match->delete();

        return ApiResponse::success(null, 'Match deleted successfully', 204);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:scheduled,in_progress,completed,cancelled',
            'current_minute' => 'sometimes|integer|min:0|max:120',
        ]);

        $match = MatchGame::findOrFail($id);
        $match->update($validated);

        return ApiResponse::success($match);
    }

    public function generateSchedule(string $tournamentId): JsonResponse
    {
        try {
            $schedule = $this->matchScheduler->generateRoundRobin((int)$tournamentId);
            return ApiResponse::created($schedule, 'Schedule generated successfully');
        } catch (\Exception $e) {
            return ApiResponse::badRequest('Failed to generate schedule: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Get currently live/recent matches.
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
    public function liveMatches(Request $request): JsonResponse
    {
        $query = MatchGame::with(['matchEvents', 'matchReport'])
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->where('match_date', '>=', now()->subHours(2))
            ->orderBy('match_date');

        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min(100, $perPage));

        $matches = $query->paginate($perPage);

        return ApiResponse::paginated($matches, 'Live matches retrieved successfully');
    }

    /**
     * Get upcoming matches.
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
    public function upcomingMatches(Request $request): JsonResponse
    {
        $query = MatchGame::with(['matchEvents', 'matchReport'])
            ->whereIn('status', ['scheduled'])
            ->where('match_date', '>', now());

        // Apply filters
        if ($request->has('tournament_id')) {
            $query->where('tournament_id', $request->tournament_id);
        }

        if ($request->has('team_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('home_team_id', $request->team_id)
                  ->orWhere('away_team_id', $request->team_id);
            });
        }

        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $matches = $query->orderBy('match_date')->paginate($perPage);

        return ApiResponse::paginated($matches, 'Upcoming matches retrieved successfully');
    }

    /**
     * Get completed matches.
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
    public function completedMatches(Request $request): JsonResponse
    {
        $query = MatchGame::with(['matchEvents', 'matchReport'])
            ->where('status', 'completed');

        // Apply filters
        if ($request->has('tournament_id')) {
            $query->where('tournament_id', $request->tournament_id);
        }

        if ($request->has('team_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('home_team_id', $request->team_id)
                  ->orWhere('away_team_id', $request->team_id);
            });
        }

        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $matches = $query->orderBy('match_date', 'desc')->paginate($perPage);

        return ApiResponse::paginated($matches, 'Completed matches retrieved successfully');
    }

    /**
     * Get matches for a given date.
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
    public function matchesByDate(Request $request, string $date): JsonResponse
    {
        try {
            // Validate date format
            $validatedDate = \DateTime::createFromFormat('Y-m-d', $date);
            if (!$validatedDate) {
                return ApiResponse::badRequest('Invalid date format. Use Y-m-d format.');
            }

            $query = MatchGame::with(['matchEvents', 'matchReport'])
                ->whereDate('match_date', $date);

            // Apply optional filters
            if ($request->has('tournament_id')) {
                $query->where('tournament_id', $request->tournament_id);
            }

            if ($request->has('team_id')) {
                $query->where(function ($q) use ($request) {
                    $q->where('home_team_id', $request->team_id)
                      ->orWhere('away_team_id', $request->team_id);
                });
            }

            $perPage = (int) $request->query('per_page', 20);
            $perPage = max(1, min(100, $perPage));

            $matches = $query->orderBy('match_date')->paginate($perPage);

            return ApiResponse::paginated($matches, "Matches for {$date} retrieved successfully");
        } catch (\Exception $e) {
            Log::error('Failed to retrieve matches by date', [
                'date' => $date,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::serverError('Failed to retrieve matches by date', $e);
        }
    }
}
