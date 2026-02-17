<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\Player;
use App\Services\AuthServiceClient;
use App\Services\TournamentServiceClient;
use App\Services\Queue\QueuePublisher;
use App\Services\Events\EventPayloadBuilder;
use App\Services\PublicCacheService;
use App\Events\TeamCreated;
use App\Events\TeamUpdated;
use App\Helpers\AuthHelper;
use App\Models\MatchGame;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TeamController extends Controller
{
    protected $authService;
    protected $tournamentService;
    protected QueuePublisher $queuePublisher;
    protected PublicCacheService $cacheService;

    public function __construct(AuthServiceClient $authService, TournamentServiceClient $tournamentService, QueuePublisher $queuePublisher, PublicCacheService $cacheService)
    {
        $this->authService = $authService;
        $this->tournamentService = $tournamentService;
        $this->queuePublisher = $queuePublisher;
        $this->cacheService = $cacheService;
    }

    public function public_index(Request $request): JsonResponse
    {
        $query = Team::with(['players']);

        if ($request->has('tournament_id')) {
            $query->where('tournament_id', $request->tournament_id);
        }

        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('logo', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $teams = $query->orderByDesc('id')->paginate($perPage);

        // Enrich teams with logo URLs
        $teams->getCollection()->transform(function ($team) {
            $team->setAttribute('logo_url', $this->getLogoUrl($team->logo));
            $team->makeVisible(['logo_url']);
            return $team;
        });

        return ApiResponse::paginated($teams, 'Teams retrieved successfully');
    }
    /**
     * Display a listing of teams.
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
    public function index(Request $request, $tournamentId = null): JsonResponse
    {
        $query = Team::with(['players']);

        // Get tournament_id from route parameter or query parameter
        $tournamentId = $tournamentId ?? $request->route('tournamentId') ?? $request->input('tournament_id');

        if ($tournamentId) {
            $query->where('tournament_id', $tournamentId);
        }

        // Apply search filter
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%' . $searchTerm . '%')
                  ->orWhere('logo', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        // If user is coach, only show their teams (check pivot table directly since users are in auth-service)
        if (AuthHelper::isCoach()) {
            $coachUserId = AuthHelper::getCurrentUserId();
            $teamIds = DB::table('team_coach')
                ->where('user_id', $coachUserId)
                ->pluck('team_id')
                ->toArray();
            if (!empty($teamIds)) {
                $query->whereIn('id', $teamIds);
            } else {
                // If coach has no teams, return empty result
                $query->whereRaw('1 = 0');
            }
        }

        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(1, min(100, $perPage));

        $paginator = $query->orderByDesc('id')->paginate($perPage);

        // Enrich teams with tournament name and coach names
        $items = collect($paginator->items())
            ->map(function ($team) {
                // Get tournament name
                $tournament = null;
                try {
                    $tournament = $this->tournamentService->getPublicTournament($team->tournament_id);
                    if (!$tournament) {
                        Log::warning('Tournament not found', [
                            'team_id' => $team->id,
                            'tournament_id' => $team->tournament_id
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to fetch tournament for team', [
                        'team_id' => $team->id,
                        'tournament_id' => $team->tournament_id,
                        'error' => $e->getMessage()
                    ]);
                }

                // Get coach names - fetch coach IDs directly from pivot table since users don't exist in team-service DB
                $coachNames = [];
                $coachIds = DB::table('team_coach')
                    ->where('team_id', $team->id)
                    ->pluck('user_id')
                    ->toArray();

                Log::info('Processing team coaches', [
                    'team_id' => $team->id,
                    'coaches_count' => count($coachIds),
                    'coach_ids' => $coachIds
                ]);

                if (!empty($coachIds)) {
                    foreach ($coachIds as $coachId) {
                        try {
                            // Fetch from auth-service to get the name
                            $coachData = $this->authService->getUser($coachId);

                            Log::info('Fetched coach data', [
                                'coach_id' => $coachId,
                                'team_id' => $team->id,
                                'has_data' => !is_null($coachData),
                                'has_name' => isset($coachData['name'])
                            ]);

                            if ($coachData && isset($coachData['name']) && !empty($coachData['name'])) {
                                $coachNames[] = $coachData['name'];
                            } else {
                                Log::warning('Coach user not found or has no name', [
                                    'coach_id' => $coachId,
                                    'team_id' => $team->id,
                                    'coach_data' => $coachData
                                ]);
                                $coachNames[] = 'Unknown';
                            }
                        } catch (\Exception $e) {
                            Log::error('Failed to fetch coach from auth-service', [
                                'coach_id' => $coachId,
                                'team_id' => $team->id,
                                'error' => $e->getMessage()
                            ]);
                            $coachNames[] = 'Unknown';
                        }
                    }
                } else {
                    Log::info('No coaches found for team', [
                        'team_id' => $team->id
                    ]);
                }

                // Set tournament data on model
                $tournamentData = $tournament ? [
                    'id' => $tournament['id'] ?? null,
                    'name' => $tournament['name'] ?? null,
                ] : null;

                $team->setAttribute('tournament', $tournamentData);
                $team->setAttribute('coaches_list', $coachNames);
                $team->setAttribute('coaches_count', count($coachNames));
                $team->setAttribute('logo_url', $this->getLogoUrl($team->logo));

                // Make sure these attributes are visible in JSON serialization
                $team->makeVisible(['tournament', 'coaches_list', 'coaches_count', 'logo_url']);

                Log::info('Team enriched', [
                    'team_id' => $team->id,
                    'tournament_name' => $tournamentData['name'] ?? null,
                    'coaches_list' => $coachNames
                ]);

                return $team;
            })
            ->all();

        $paginator->setCollection(collect($items));

        return ApiResponse::paginated($paginator, 'Teams retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        // Debug: Log request details before validation
        Log::info('Team creation request received', [
            'content_type' => $request->header('Content-Type'),
            'has_file_logo' => $request->hasFile('logo'),
            'all_files' => array_keys($request->allFiles()),
            'request_keys' => array_keys($request->all()),
            'request_method' => $request->method(),
            'is_multipart' => str_contains($request->header('Content-Type', ''), 'multipart/form-data'),
            'logo_file_info' => $request->hasFile('logo') ? [
                'name' => $request->file('logo')->getClientOriginalName(),
                'size' => $request->file('logo')->getSize(),
                'mime' => $request->file('logo')->getMimeType(),
            ] : null
        ]);

        $validator = Validator::make($request->all(), [
            'tournament_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'coach_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            Log::warning('Team creation validation failed', [
                'errors' => $validator->errors()->toArray()
            ]);
            return ApiResponse::validationError($validator->errors());
        }

        try {
            // Validate tournament exists
            $tournamentResponse = $this->tournamentService->validateTournament($request->tournament_id);
            if (!($tournamentResponse['success'] ?? false)) {
                return ApiResponse::badRequest('Invalid tournament');
            }

            // Validate coach exists
            $coachResponse = $this->authService->validateUser($request->coach_id);
            if (!($coachResponse['success'] ?? false)) {
                return ApiResponse::badRequest('Invalid coach');
            }

            DB::beginTransaction();

            // Handle logo upload
            $logoPath = null;

            if ($request->hasFile('logo')) {
                try {
                    $logoFile = $request->file('logo');
                    $logoName = time() . '_' . uniqid() . '.' . $logoFile->getClientOriginalExtension();

                    // Ensure logos directory exists
                    $logosDir = public_path('logos');
                    if (!file_exists($logosDir)) {
                        mkdir($logosDir, 0755, true);
                    }

                    // Store logo in public/logos directory
                    $logoFile->move($logosDir, $logoName);
                    $logoPath = 'logos/' . $logoName;

                    Log::info('Team logo uploaded successfully', [
                        'logo_path' => $logoPath,
                        'file_name' => $logoName
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to upload team logo', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Continue without logo rather than failing the entire request
                }
            }

            // Create team
            $team = Team::create([
                'tournament_id' => $request->tournament_id,
                'name' => $request->name,
                'logo' => $logoPath,
            ]);

            // Attach coach to team (direct DB insert since users are in auth-service)
            // Use insertOrIgnore to avoid duplicate key errors
            DB::table('team_coach')->insertOrIgnore([
                'team_id' => $team->id,
                'user_id' => $request->coach_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            // Fire legacy event
            event(new TeamCreated($team, $request->coach_id));

            // Enrich team data with logo URL
            $enrichedTeam = $this->enrichTeamData($team);

            return ApiResponse::created($enrichedTeam, 'Team created successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::serverError('Failed to create team: ' . $e->getMessage(), $e);
        }
    }

    public function show(string $id): JsonResponse
    {
        $team = Team::with(['players'])->find($id);

        if (!$team) {
            return ApiResponse::notFound('Team not found');
        }

        // Authorization:
        // - Public route (/public/teams/{id}) is readable by unauthenticated users
        // - If a user is authenticated and is not an admin, they must be a coach of this team
        $currentUserId = AuthHelper::getCurrentUserId();
        if ($currentUserId && !AuthHelper::isAdmin() && !AuthHelper::canManageTeam($id)) {
            return ApiResponse::forbidden('Unauthorized');
        }

        // Enrich team data with logo URL
        $enrichedTeam = $this->enrichTeamData($team);

        return ApiResponse::success($enrichedTeam);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $team = Team::find($id);

        if (!$team) {
            return ApiResponse::notFound('Team not found');
        }

        // Check authorization
        if (!AuthHelper::canManageTeam($id)) {
            return ApiResponse::forbidden('Unauthorized');
        }

        // Debug: Log request details before validation
        Log::info('Team update request received', [
            'team_id' => $id,
            'content_type' => $request->header('Content-Type'),
            'has_file_logo' => $request->hasFile('logo'),
            'all_files' => array_keys($request->allFiles()),
            'request_keys' => array_keys($request->all()),
            'request_method' => $request->method(),
            'is_multipart' => str_contains($request->header('Content-Type', ''), 'multipart/form-data'),
            'logo_file_info' => $request->hasFile('logo') ? [
                'name' => $request->file('logo')->getClientOriginalName(),
                'size' => $request->file('logo')->getSize(),
                'mime' => $request->file('logo')->getMimeType(),
            ] : null,
            'current_logo' => $team->logo
        ]);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'logo' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'coach_id' => 'sometimes|integer'
        ]);

        if ($validator->fails()) {
            Log::warning('Team update validation failed', [
                'team_id' => $id,
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->except(['logo']) // Exclude file from log
            ]);
            return ApiResponse::validationError($validator->errors());
        }

        try {
            $oldData = $team->toArray();

            // Handle logo upload
            $logoPath = $team->logo; // Keep existing logo by default
            if ($request->hasFile('logo')) {
                try {
                    // Delete old logo if exists
                    if ($team->logo && file_exists(public_path($team->logo))) {
                        unlink(public_path($team->logo));
                    }

                    $logoFile = $request->file('logo');
                    $logoName = time() . '_' . uniqid() . '.' . $logoFile->getClientOriginalExtension();

                    // Ensure logos directory exists
                    $logosDir = public_path('logos');
                    if (!file_exists($logosDir)) {
                        mkdir($logosDir, 0755, true);
                    }

                    // Store new logo
                    $logoFile->move($logosDir, $logoName);
                    $logoPath = 'logos/' . $logoName;

                    Log::info('Team logo updated successfully', [
                        'team_id' => $team->id,
                        'logo_path' => $logoPath,
                        'file_name' => $logoName
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to update team logo', [
                        'team_id' => $team->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Keep existing logo if upload fails
                }
            }

            // If coach_id provided, validate and update coach assignment
            if ($request->has('coach_id')) {
                // Validate coach exists in auth-service
                $coachResponse = $this->authService->validateUser($request->coach_id);
                if (!($coachResponse['success'] ?? false)) {
                    return ApiResponse::badRequest('Invalid coach');
                }

                // Replace existing coach assignments for this team
                DB::table('team_coach')
                    ->where('team_id', $team->id)
                    ->delete();

                DB::table('team_coach')->insertOrIgnore([
                    'team_id' => $team->id,
                    'user_id' => $request->coach_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Build update data
            $updateData = [
                'name' => $request->has('name') ? trim($request->name) : $team->name,
                'logo' => $logoPath,
            ];

            $team->update($updateData);

            Log::info('Team updated successfully', [
                'team_id' => $team->id,
                'name_changed' => $request->has('name') && trim($request->name) !== ($oldData['name'] ?? ''),
                'logo_changed' => $request->hasFile('logo')
            ]);

            // Immediately invalidate public API cache for this team
            $this->invalidateTeamCache($team);

            // Fire legacy event
            event(new TeamUpdated($team, AuthHelper::getCurrentUserId()));

            // Enrich team data with logo URL
            $enrichedTeam = $this->enrichTeamData($team);

            return ApiResponse::success($enrichedTeam, 'Team updated successfully');

        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to update team: ' . $e->getMessage(), $e);
        }
    }

    public function destroy(string $id): JsonResponse
    {
        $team = Team::find($id);

        if (!$team) {
            return ApiResponse::notFound('Team not found');
        }

        // Only admin can delete teams
        if (!AuthHelper::isAdmin()) {
            return ApiResponse::forbidden('Unauthorized. Only admin can delete teams.');
        }

        // Store team data for logging before deletion
        $teamData = [
            'id' => $team->id,
            'name' => $team->name,
            'tournament_id' => $team->tournament_id,
            'logo' => $team->logo
        ];

        Log::info('Team deletion request received', [
            'team_id' => $id,
            'team_name' => $teamData['name'],
            'tournament_id' => $teamData['tournament_id'],
            'has_logo' => !empty($teamData['logo']),
            'logo_path' => $teamData['logo']
        ]);

        try {
            DB::beginTransaction();

            // Delete logo file if exists
            if ($team->logo) {
                try {
                    $logoPath = public_path($team->logo);
                    if (file_exists($logoPath)) {
                        unlink($logoPath);
                        Log::info('Team logo deleted successfully', [
                            'team_id' => $team->id,
                            'logo_path' => $team->logo
                        ]);
                    } else {
                        Log::warning('Team logo file not found during deletion', [
                            'team_id' => $team->id,
                            'logo_path' => $team->logo,
                            'full_path' => $logoPath
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to delete team logo file', [
                        'team_id' => $team->id,
                        'logo_path' => $team->logo,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Continue with team deletion even if logo deletion fails
                }
            }

            // Detach coaches (direct DB delete since users are in auth-service)
            DB::table('team_coach')->where('team_id', $team->id)->delete();

            // Delete players
            $team->players()->delete();

            // Delete team
            $team->delete();

            DB::commit();

            Log::info('Team deleted successfully', [
                'team_id' => $teamData['id'],
                'team_name' => $teamData['name'],
                'tournament_id' => $teamData['tournament_id']
            ]);


            return ApiResponse::success(null, 'Team deleted successfully');

        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::serverError('Failed to delete team: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Get team overview
     */
    public function overview(string $id): JsonResponse
    {
        try {
            $team = Team::with(['players'])
                ->findOrFail($id);

            // Calculate team statistics
            $totalPlayers = $team->players->count();
            $activePlayers = $team->players()->count(); // Remove status filter for now

            return ApiResponse::success([
                'team' => $team,
                'statistics' => [
                    'total_players' => $totalPlayers,
                    'active_players' => $activePlayers,
                ]
            ], 'Team overview retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve team overview', $e);
        }
    }

    /**
     * Get team squad
     *
     * This endpoint returns a gateway-compatible paginated response.
     */
    public function squad(Request $request, string $id): JsonResponse
    {
        try {
            $team = Team::findOrFail($id);

            $perPage = (int) $request->query('per_page', 20);
            $perPage = max(1, min(100, $perPage));

            $players = $team->players()->orderByDesc('id')->paginate($perPage);

            return ApiResponse::paginated($players, 'Team squad retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve team squad', $e);
        }
    }

    /**
     * Get team matches
     */
    public function matches(Request $request, string $id): JsonResponse
    {
        try {
            $team = Team::findOrFail($id);

            // Call match service to get team matches
            $matchServiceUrl = config('services.match_service.url', 'http://match-service:8004');
            $response = Http::get("{$matchServiceUrl}/api/public/matches", [
                'team_id' => $id,
                'per_page' => 50
            ]);

            if (!$response->successful()) {
                return ApiResponse::error('Failed to retrieve matches from match service', 503, 'Match service unavailable');
            }

            $matchData = $response->json();

            // Enrich match data with team information
            $matches = collect($matchData['data'] ?? [])->map(function ($match) use ($team) {
                $match['is_home'] = $match['home_team_id'] == $team->id;
                $match['opponent'] = $match['is_home'] ?
                    ($match['away_team'] ?? ['name' => 'Unknown Team']) :
                    ($match['home_team'] ?? ['name' => 'Unknown Team']);
                $match['team_score'] = $match['is_home'] ? $match['home_score'] : $match['away_score'];
                $match['opponent_score'] = $match['is_home'] ? $match['away_score'] : $match['home_score'];
                $match['result'] = $this->determineMatchResult($match['team_score'], $match['opponent_score'], $match['status']);
                return $match;
            });

            return ApiResponse::success([
                'matches' => $matches,
                'meta' => $matchData['meta'] ?? [],
                'team' => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'short_name' => $team->short_name
                ]
            ], 'Team matches retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve team matches', $e);
        }
    }

    /**
     * Determine match result for the team
     */
    private function determineMatchResult(?int $teamScore, ?int $opponentScore, string $status): string
    {
        if ($status !== 'completed' || is_null($teamScore) || is_null($opponentScore)) {
            return 'pending';
        }

        if ($teamScore > $opponentScore) {
            return 'win';
        } elseif ($teamScore < $opponentScore) {
            return 'loss';
        } else {
            return 'draw';
        }
    }

    /**
     * Get team statistics
     */
    public function statistics(string $id): JsonResponse
    {
        try {
            $team = Team::findOrFail($id);

            // Simple statistics based on players count
            $totalPlayers = $team->players()->count();

            return ApiResponse::success([
                'team_id' => (int)$id,
                'total_players' => $totalPlayers,
                'team_name' => $team->name,
            ], 'Team statistics retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve team statistics', $e);
        }
    }

    /**
     * Get full logo URL for a team
     */
    protected function getLogoUrl(?string $logoPath): ?string
    {
        if (!$logoPath) {
            return null;
        }

        // Return full URL to the logo
        return url($logoPath);
    }

    /**
     * Enrich team data with additional information
     */
    protected function enrichTeamData($team): array
    {
        $data = $team->toArray();

        // Add full logo URL
        $data['logo_url'] = $this->getLogoUrl($team->logo);

        return $data;
    }

    /**
     * Immediately invalidate public API cache for a team
     *
     * @param Team $team
     * @return void
     */
    protected function invalidateTeamCache(Team $team): void
    {
        try {
            $tags = [
                'public-api',
                'teams',
                "team:{$team->id}",
                "public:team:{$team->id}",
                "public:team:{$team->id}:players",
                "public:team:{$team->id}:matches",
            ];

            // Also invalidate tournament teams cache if tournament_id is available
            if ($team->tournament_id) {
                $tags[] = "tournament:{$team->tournament_id}";
                $tags[] = "public:tournament:{$team->tournament_id}:teams";
            }

            $this->cacheService->forgetByTags($tags);

            Log::info('Team cache invalidated immediately', [
                'team_id' => $team->id,
                'tournament_id' => $team->tournament_id,
                'tags' => $tags
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to invalidate team cache immediately', [
                'team_id' => $team->id,
                'error' => $e->getMessage()
            ]);
            // Don't throw - cache invalidation failure shouldn't break the update
        }
    }
}
