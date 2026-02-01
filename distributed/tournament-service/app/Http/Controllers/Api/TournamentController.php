<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;

use App\Models\Tournament;
use App\Models\TournamentSettings;
use App\Services\AuthService;
use App\Services\Queue\QueuePublisher;
use App\Services\Clients\MatchServiceClient;
use App\Services\Clients\ResultsServiceClient;
use App\Services\Clients\TeamServiceClient;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\LengthAwarePaginator;

class TournamentController extends Controller
{
    protected AuthService $authService;
    protected QueuePublisher $queuePublisher;
    protected MatchServiceClient $matchServiceClient;
    protected ResultsServiceClient $resultsServiceClient;
    protected TeamServiceClient $teamServiceClient;

    public function __construct(
        AuthService $authService,
        QueuePublisher $queuePublisher,
        MatchServiceClient $matchServiceClient,
        ResultsServiceClient $resultsServiceClient,
        TeamServiceClient $teamServiceClient
    ) {
        $this->authService = $authService;
        $this->queuePublisher = $queuePublisher;
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

            return ApiResponse::serverError('Failed to retrieve tournaments', $e);
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
                return ApiResponse::unauthorized('User not authenticated');
            }

            // Check if user has admin role OR manage_tournaments permission
            $isAdmin = collect($userRoles)->contains('name', 'Administrator');
            $canManageTournaments = $this->authService->userHasPermission(['data' => ['permissions' => $userPermissions]], 'manage_tournaments');

            if (!$isAdmin && !$canManageTournaments) {
                return ApiResponse::forbidden('Tournament management access required');
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

            // Dispatch tournament created event to queue (default priority)
            $this->dispatchTournamentCreatedQueueEvent($tournament, $user);

            return ApiResponse::created($tournament->load(['sport', 'settings']), 'Tournament created successfully');
        } catch (\Exception $e) {
            Log::error('Failed to create tournament', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::serverError('Failed to create tournament', $e);
        }
    }

    /**
     * Display the specified tournament with settings.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $tournament = Tournament::with(['sport', 'settings'])->find($id);

            Log::info('Tournament retrieved successfully', [
                'tournament_id' => $id,
                'tournament_name' => $tournament ? $tournament->name : null
            ]);
            if (!$tournament) {
                return ApiResponse::notFound('Tournament not found');
            }

            return ApiResponse::success($tournament, 'Tournament retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to retrieve tournament', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::serverError('Failed to retrieve tournament', $e);
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
                return ApiResponse::notFound('Tournament not found');
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

            // Dispatch tournament updated event to queue (default priority)
            $this->dispatchTournamentUpdatedQueueEvent($tournament, $oldData);

            return ApiResponse::success($tournament->load(['sport', 'settings']), 'Tournament updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to update tournament', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::serverError('Failed to update tournament', $e);
        }
    }

    /**
     * Remove the specified tournament.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        try {
            $tournament = Tournament::with(['sport'])->find($id);

            if (!$tournament) {
                return ApiResponse::notFound('Tournament not found');
            }

            // Get tournament data before deletion for event
            $tournamentData = [
                'id' => $tournament->id,
                'name' => $tournament->name,
                'status' => $tournament->status,
                'sport_id' => $tournament->sport_id,
                'start_date' => $tournament->start_date?->toIso8601String(),
                'end_date' => $tournament->end_date?->toIso8601String(),
            ];

            // Get authenticated user from middleware
            $user = $request->get('authenticated_user', []);

            // Delete tournament
            $tournament->delete();

            Log::info('Tournament deleted successfully', [
                'tournament_id' => $id,
                'tournament_name' => $tournamentData['name']
            ]);

            // Dispatch tournament deleted event to queue (high priority - critical)
            $this->dispatchTournamentDeletedQueueEvent($tournamentData, $user);

            return ApiResponse::success(null, 'Tournament deleted successfully');
        } catch (\Exception $e) {
            Log::error('Failed to delete tournament', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::serverError('Failed to delete tournament', $e);
        }
    }

    /**
     * Update tournament status.
     */
    public function updateStatus(Request $request, string $id): JsonResponse
    {
        try {
            // Log incoming request data for debugging
            Log::info('Tournament status update request', [
                'tournament_id' => $id,
                'request_data' => $request->all(),
                'request_method' => $request->method(),
                'request_headers' => $request->headers->all()
            ]);

            $tournament = Tournament::find($id);

            if (!$tournament) {
                return ApiResponse::notFound('Tournament not found');
            }

            // Log the status value being validated
            $statusValue = $request->input('status');
            Log::info('Validating tournament status', [
                'tournament_id' => $id,
                'status_value' => $statusValue,
                'status_type' => gettype($statusValue),
                'allowed_values' => ['planned', 'ongoing', 'completed', 'cancelled']
            ]);

            // Normalize the status value - trim whitespace and convert to lowercase
            if (is_string($statusValue)) {
                $normalizedStatus = strtolower(trim($statusValue));
                $request->merge(['status' => $normalizedStatus]);

                Log::info('Normalized tournament status', [
                    'original_status' => $statusValue,
                    'normalized_status' => $normalizedStatus
                ]);
            }

            $validated = $request->validate([
                'status' => 'required|string|in:planned,ongoing,completed,cancelled'
            ], [
                'status.required' => 'The status field is required.',
                'status.string' => 'The status must be a string.',
                'status.in' => 'The selected status is invalid. Allowed values are: planned, ongoing, completed, cancelled.'
            ]);

            // Validate status transition
            $currentStatus = $tournament->status;
            $newStatus = $validated['status'];

            if (!$this->isValidStatusTransition($currentStatus, $newStatus)) {
                return ApiResponse::badRequest("Cannot transition from {$currentStatus} to {$newStatus}", [
                    'current_status' => $currentStatus,
                    'requested_status' => $newStatus
                ]);
            }

            $tournament->update(['status' => $newStatus]);

            Log::info('Tournament status updated successfully', [
                'tournament_id' => $tournament->id,
                'old_status' => $currentStatus,
                'new_status' => $newStatus
            ]);

            // Dispatch tournament status changed event to queue (high priority - critical)
            $this->dispatchTournamentStatusChangedQueueEvent($tournament, $currentStatus);

            return ApiResponse::success($tournament, 'Tournament status updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log detailed validation error information
            Log::error('Tournament status validation failed', [
                'tournament_id' => $id,
                'request_data' => $request->all(),
                'validation_errors' => $e->errors(),
                'failed_rules' => $e->validator->failed()
            ]);

            return ApiResponse::validationError($e->errors(), 'Invalid status value provided');
        } catch (\Exception $e) {
            Log::error('Failed to update tournament status', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::serverError('Failed to update tournament status', $e);
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
                return ApiResponse::notFound('Tournament not found');
            }

            return ApiResponse::success([
                'id' => $tournament->id,
                'name' => $tournament->name,
                'status' => $tournament->status,
                'sport_id' => $tournament->sport_id
            ], 'Tournament is valid');
        } catch (\Exception $e) {
            Log::error('Failed to validate tournament', [
                'tournament_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return ApiResponse::serverError('Failed to validate tournament', $e);
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
                return ApiResponse::notFound('Tournament not found');
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
            
            // If match service is unavailable, return empty matches instead of error
            if (!$matchesResponse['success']) {
                $isServiceUnavailable = $matchesResponse['service_unavailable'] ?? false;
                
                if ($isServiceUnavailable) {
                    // Service is not running - return empty matches gracefully
                    Log::warning('MatchService is unavailable, returning empty matches', [
                        'tournament_id' => $id,
                        'error' => $matchesResponse['error']
                    ]);
                    
                    $matches = [];
                    $total = 0;
                } else {
                    // Other error - return error response
                    Log::error('Failed to fetch matches from MatchService', [
                        'tournament_id' => $id,
                        'error' => $matchesResponse['error']
                    ]);

                    return ApiResponse::error('Failed to retrieve tournament matches', $matchesResponse['status'] ?? 500, $matchesResponse['error']);
                }
            } else {
                $matches = $matchesResponse['data']['data'] ?? [];
                $total = (int) ($matchesResponse['data']['meta']['total'] ?? count($matches));
            }

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

            return ApiResponse::serverError('Failed to retrieve tournament matches', $e);
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

            // If team service is unavailable, return empty teams instead of error
            if (!$teamsResponse['success']) {
                $isServiceUnavailable = $teamsResponse['service_unavailable'] ?? false;
                
                if ($isServiceUnavailable) {
                    // Service is not running - return empty teams gracefully
                    Log::warning('TeamService is unavailable, returning empty teams', [
                        'tournament_id' => $id,
                        'error' => $teamsResponse['error']
                    ]);
                    
                    $teams = [];
                } else {
                    // Other error - return error response
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
            } else {
                $teams = $teamsResponse['data'] ?? [];
            }

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
     * Dispatch tournament created event to queue
     *
     * @param Tournament $tournament
     * @param array $user
     * @return void
     */
    protected function dispatchTournamentCreatedQueueEvent(Tournament $tournament, array $user): void
    {
        try {
            $this->queuePublisher->dispatchNormal('events', [
                'tournament_id' => $tournament->id,
                'name' => $tournament->name,
                'sport_id' => $tournament->sport_id,
                'location' => $tournament->location,
                'start_date' => $tournament->start_date?->toIso8601String(),
                'end_date' => $tournament->end_date?->toIso8601String(),
                'status' => $tournament->status,
                'created_by' => $user['id'] ?? null,
                'created_at' => now()->toIso8601String(),
            ], 'tournament.created');
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch tournament created queue event', [
                'tournament_id' => $tournament->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Dispatch tournament updated event to queue
     *
     * @param Tournament $tournament
     * @param array $oldData
     * @return void
     */
    protected function dispatchTournamentUpdatedQueueEvent(Tournament $tournament, array $oldData): void
    {
        try {
            $this->queuePublisher->dispatchNormal('events', [
                'tournament_id' => $tournament->id,
                'name' => $tournament->name,
                'sport_id' => $tournament->sport_id,
                'location' => $tournament->location,
                'start_date' => $tournament->start_date?->toIso8601String(),
                'end_date' => $tournament->end_date?->toIso8601String(),
                'status' => $tournament->status,
                'old_data' => $oldData,
                'updated_fields' => array_keys($tournament->getChanges()),
                'updated_at' => now()->toIso8601String(),
            ], 'tournament.updated');
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch tournament updated queue event', [
                'tournament_id' => $tournament->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Dispatch tournament status changed event to queue (high priority)
     *
     * @param Tournament $tournament
     * @param string $oldStatus
     * @return void
     */
    protected function dispatchTournamentStatusChangedQueueEvent(Tournament $tournament, string $oldStatus): void
    {
        try {
            $this->queuePublisher->dispatchHigh('events', [
                'tournament_id' => $tournament->id,
                'old_status' => $oldStatus,
                'new_status' => $tournament->status,
                'name' => $tournament->name,
                'sport_id' => $tournament->sport_id,
                'changed_at' => now()->toIso8601String(),
            ], 'tournament.status.changed');
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch tournament status changed queue event', [
                'tournament_id' => $tournament->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Dispatch tournament deleted event to queue (high priority)
     *
     * @param array $tournamentData
     * @param array $user
     * @return void
     */
    protected function dispatchTournamentDeletedQueueEvent(array $tournamentData, array $user): void
    {
        try {
            $this->queuePublisher->dispatchHigh('events', [
                'tournament_id' => $tournamentData['id'],
                'id' => $tournamentData['id'],
                'name' => $tournamentData['name'],
                'status' => $tournamentData['status'],
                'sport_id' => $tournamentData['sport_id'],
                'start_date' => $tournamentData['start_date'],
                'end_date' => $tournamentData['end_date'],
                'deleted_by' => $user['id'] ?? null,
                'deleted_at' => now()->toIso8601String(),
            ], 'tournament.deleted');
        } catch (\Exception $e) {
            Log::warning('Failed to dispatch tournament deleted queue event', [
                'tournament_id' => $tournamentData['id'],
                'error' => $e->getMessage()
            ]);
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
