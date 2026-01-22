<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\TournamentSettings;
use App\Services\AuthService;
use App\Services\EventPublisher;
use App\Services\Clients\MatchServiceClient;
use App\Services\Clients\ResultsServiceClient;
use App\Services\Clients\TeamServiceClient;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

class TournamentController extends Controller
{
    protected AuthService $authService;
    protected EventPublisher $eventPublisher;
    protected MatchServiceClient $matchServiceClient;
    protected ResultsServiceClient $resultsServiceClient;
    protected TeamServiceClient $teamServiceClient;

    public function __construct(AuthService $authService, EventPublisher $eventPublisher, MatchServiceClient $matchServiceClient, ResultsServiceClient $resultsServiceClient, TeamServiceClient $teamServiceClient)
    {
        $this->authService = $authService;
        $this->eventPublisher = $eventPublisher;
        $this->matchServiceClient = $matchServiceClient;
        $this->resultsServiceClient = $resultsServiceClient;
        $this->teamServiceClient = $teamServiceClient;
    }

    /**
     * Display a listing of tournaments with filters.
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Tournament::with(['sport', 'settings']);

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('sport_id')) {
                $query->where('sport_id', $request->sport_id);
            }

            $perPage = (int) $request->query('per_page', 20);
            $perPage = max(1, min(100, $perPage));

            $tournaments = $query->orderByDesc('start_date')->paginate($perPage);

            return ApiResponse::paginated($tournaments, 'Tournaments retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve tournaments', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tournaments',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created tournament (Admin only).
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Get authenticated user from middleware
            $user = $request->get('authenticated_user');
            $userRoles = $request->get('user_roles', []);
            $userPermissions = $request->get('user_permissions', []);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. User not authenticated.',
                    'error' => 'No authenticated user'
                ], 401);
            }

            // Check if user has admin role OR manage_tournaments permission
            $isAdmin = collect($userRoles)->contains('name', 'Administrator');
            $canManageTournaments = $this->authService->userHasPermission(['data' => ['permissions' => $userPermissions]], 'manage_tournaments');

            if (!$isAdmin && !$canManageTournaments) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Tournament management access required.',
                    'error' => 'Insufficient permissions',
                    'user_roles' => $userRoles,
                    'user_permissions' => $userPermissions
                ], 403);
            }

            $validated = $request->validate([
                'sport_id' => 'required|exists:sports,id',
                'name' => 'required|string|max:255',
                'location' => 'nullable|string|max:500',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after:start_date',
                'status' => 'sometimes|in:planned,ongoing,completed,cancelled'
            ]);

            $validated['created_by'] = $user['id'];

            $tournament = Tournament::create($validated);

            Log::info('Tournament created successfully', [
                'tournament_id' => $tournament->id,
                'name' => $tournament->name,
                'user_id' => $user['id']
            ]);

            // Publish tournament created event
            $this->eventPublisher->publishTournamentCreated($tournament->load(['sport', 'settings'])->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Tournament created successfully',
                'data' => $tournament->load(['sport', 'settings'])
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create tournament', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create tournament',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified tournament with settings.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $tournament = Tournament::with(['sport', 'settings'])->find($id);

            if (!$tournament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Tournament retrieved successfully',
                'data' => $tournament
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve tournament', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tournament',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update the specified tournament.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $tournament = Tournament::find($id);

            if (!$tournament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            $validated = $request->validate([
                'sport_id' => 'sometimes|exists:sports,id',
                'name' => 'sometimes|string|max:255',
                'location' => 'nullable|string|max:500',
                'start_date' => 'sometimes|date|after_or_equal:today',
                'end_date' => 'sometimes|date|after:start_date',
                'status' => 'sometimes|in:planned,ongoing,completed,cancelled'
            ]);

            $oldData = $tournament->toArray();
            $tournament->update($validated);

            Log::info('Tournament updated successfully', [
                'tournament_id' => $tournament->id,
                'name' => $tournament->name
            ]);

            // Publish tournament updated event
            $this->eventPublisher->publishTournamentUpdated(
                $tournament->load(['sport', 'settings'])->toArray(),
                $oldData
            );

            return response()->json([
                'success' => true,
                'message' => 'Tournament updated successfully',
                'data' => $tournament->load(['sport', 'settings'])
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update tournament', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update tournament',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified tournament.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $tournament = Tournament::find($id);

            if (!$tournament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            $tournament->delete();

            Log::info('Tournament deleted successfully', [
                'tournament_id' => $id,
                'tournament_name' => $tournament->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tournament deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete tournament', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete tournament',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update tournament status.
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        try {
            $tournament = Tournament::find($id);

            if (!$tournament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            $validated = $request->validate([
                'status' => 'required|in:planned,ongoing,completed,cancelled'
            ]);

            // Validate status transition
            $currentStatus = $tournament->status;
            $newStatus = $validated['status'];

            if (!$this->isValidStatusTransition($currentStatus, $newStatus)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status transition',
                    'error' => "Cannot transition from {$currentStatus} to {$newStatus}",
                    'current_status' => $currentStatus,
                    'requested_status' => $newStatus
                ], 400);
            }

            $tournament->update(['status' => $newStatus]);

            Log::info('Tournament status updated successfully', [
                'tournament_id' => $tournament->id,
                'old_status' => $currentStatus,
                'new_status' => $newStatus
            ]);

            // Publish tournament status changed event
            $this->eventPublisher->publishTournamentStatusChanged(
                $tournament->load(['sport', 'settings'])->toArray(),
                $currentStatus
            );

            return response()->json([
                'success' => true,
                'message' => 'Tournament status updated successfully',
                'data' => $tournament
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update tournament status', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update tournament status',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Validate if a tournament exists and is accessible.
     */
    public function validateTournament(string $id): JsonResponse
    {
        try {
            $tournament = Tournament::find($id);

            if (!$tournament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Tournament is valid',
                'data' => [
                    'id' => $tournament->id,
                    'name' => $tournament->name,
                    'status' => $tournament->status,
                    'sport_id' => $tournament->sport_id
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to validate tournament', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to validate tournament',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get matches for a specific tournament.
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
    public function getTournamentMatches(Request $request, string $id): JsonResponse
    {
        try {
            $tournament = Tournament::find($id);

            if (!$tournament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            Log::info("Fetching tournament matches for tournament {$id}");

            $page = (int) $request->query('page', 1);
            $page = max(1, $page);
            $perPage = (int) $request->query('per_page', 20);
            $perPage = max(1, min(100, $perPage));

            $filters = $request->all();
            $filters['page'] = $page;
            $filters['per_page'] = $perPage;

            // Fetch matches from MatchService
            $matchesResponse = $this->matchServiceClient->getTournamentMatches($tournament->id, $filters);

            Log::info("Fetched tournament matches for tournament {$id}", [
                'matches_response' => $matchesResponse
            ]);
            if (!$matchesResponse['success']) {
                Log::error('Failed to fetch matches from MatchService', [
                    'tournament_id' => $id,
                    'error' => $matchesResponse['error']
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve tournament matches',
                    'error' => $matchesResponse['error']
                ], $matchesResponse['status'] ?? 500);
            }

            $matches = $matchesResponse['data']['data'] ?? [];
            $total = (int) ($matchesResponse['data']['meta']['total'] ?? count($matches));

            $paginator = new LengthAwarePaginator(
                $matches,
                $total,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            return ApiResponse::paginated($paginator, 'Tournament matches retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve tournament matches', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tournament matches',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get teams for a specific tournament.
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
    public function getTournamentTeams(Request $request, string $id): JsonResponse
    {
        try {
            $tournament = Tournament::find($id);

            if (!$tournament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            $page = (int) $request->query('page', 1);
            $page = max(1, $page);
            $perPage = (int) $request->query('per_page', 20);
            $perPage = max(1, min(100, $perPage));

            // Fetch teams from TeamService
            $teamsResponse = $this->teamServiceClient->getTournamentTeams($tournament->id);

            if (!$teamsResponse['success']) {
                Log::error('Failed to fetch teams from TeamService', [
                    'tournament_id' => $id,
                    'error' => $teamsResponse['error']
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve tournament teams',
                    'error' => $teamsResponse['error']
                ], $teamsResponse['status'] ?? 500);
            }

            $teams = $teamsResponse['data'] ?? [];

            $total = count($teams);
            $offset = ($page - 1) * $perPage;
            $pageItems = array_slice($teams, $offset, $perPage);

            $paginator = new LengthAwarePaginator(
                $pageItems,
                $total,
                $perPage,
                $page,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );

            return ApiResponse::paginated($paginator, 'Tournament teams retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve tournament teams', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tournament teams',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get tournament overview with comprehensive information.
     */
    public function getTournamentOverview(Request $request, string $id): JsonResponse
    {
        try {
            $tournament = Tournament::with(['sport', 'settings'])->find($id);

            if (!$tournament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            // Get teams count
            $teamsResponse = $this->teamServiceClient->getTournamentTeams($tournament->id);
            $teamsCount = $teamsResponse['success'] ? count($teamsResponse['data']) : 0;

            // Get matches count
            $matchesResponse = $this->matchServiceClient->getTournamentMatches($tournament->id, ['limit' => 1]);
            $matchesData = $matchesResponse['success'] ? $matchesResponse['data']['data'] ?? [] : [];
            $matchesCount = $matchesData['pagination']['total'] ?? 0;

            return response()->json([
                'success' => true,
                'message' => 'Tournament overview retrieved successfully',
                'data' => [
                    'tournament' => $tournament,
                    'statistics' => [
                        'teams_count' => $teamsCount,
                        'matches_count' => $matchesCount,
                        'status' => $tournament->status,
                        'days_remaining' => now()->diffInDays($tournament->start_date, false),
                        'is_ongoing' => $tournament->status === 'ongoing',
                        'is_completed' => $tournament->status === 'completed'
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve tournament overview', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tournament overview',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get tournament statistics.
     */
    public function getTournamentStatistics(Request $request, string $id): JsonResponse
    {
        try {
            $tournament = Tournament::find($id);

            if (!$tournament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            // Fetch statistics from ResultsService
            $statisticsResponse = $this->resultsServiceClient->getTournamentStatistics($tournament->id);

            if (!$statisticsResponse['success']) {
                Log::error('Failed to fetch statistics from ResultsService', [
                    'tournament_id' => $id,
                    'error' => $statisticsResponse['error']
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve tournament statistics',
                    'error' => $statisticsResponse['error']
                ], $statisticsResponse['status'] ?? 500);
            }

            $statistics = $statisticsResponse['data'] ?? [];

            return response()->json([
                'success' => true,
                'message' => 'Tournament statistics retrieved successfully',
                'data' => [
                    'tournament_id' => $tournament->id,
                    'tournament_name' => $tournament->name,
                    'statistics' => $statistics
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve tournament statistics', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tournament statistics',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get tournament standings.
     */
    public function getTournamentStandings(Request $request, string $id): JsonResponse
    {
        try {
            $tournament = Tournament::find($id);

            if (!$tournament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tournament not found',
                    'error' => 'Resource not found'
                ], 404);
            }

            // Fetch standings from ResultsService
            $standingsResponse = $this->resultsServiceClient->getStandings($tournament->id);

            if (!$standingsResponse['success']) {
                Log::error('Failed to fetch standings from ResultsService', [
                    'tournament_id' => $id,
                    'error' => $standingsResponse['error']
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve tournament standings',
                    'error' => $standingsResponse['error']
                ], $standingsResponse['status'] ?? 500);
            }

            $standings = $standingsResponse['data'] ?? [];

            return response()->json([
                'success' => true,
                'message' => 'Tournament standings retrieved successfully',
                'data' => [
                    'tournament_id' => $tournament->id,
                    'tournament_name' => $tournament->name,
                    'standings' => $standings
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve tournament standings', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve tournament standings',
                'error' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Validate if status transition is allowed.
     */
    private function isValidStatusTransition(string $from, string $to): bool
    {
        $validTransitions = [
            'planned' => ['ongoing', 'cancelled'],
            'ongoing' => ['completed', 'cancelled'],
            'completed' => [], // No transitions from completed
            'cancelled' => ['planned'] // Can restart cancelled tournaments
        ];

        return in_array($to, $validTransitions[$from] ?? []);
    }
}
