<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class StatisticsController extends Controller
{
    /**
     * Get aggregated statistics from all services
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            // Cache the statistics for 5 minutes to reduce load
            $statistics = Cache::remember('system_statistics', 300, function () {
                return $this->aggregateStatistics();
            });

            return ApiResponse::success($statistics, 'Statistics retrieved successfully');
        } catch (\Exception $e) {
            Log::error('Error retrieving statistics: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return ApiResponse::serverError('Failed to retrieve statistics', $e);
        }
    }

    /**
     * Aggregate statistics from all services
     *
     * @return array
     */
    private function aggregateStatistics(): array
    {
        $statistics = [
            'users' => $this->getUserStatistics(),
            'tournaments' => $this->getTournamentStatistics(),
            'sports' => $this->getSportStatistics(),
            'venues' => $this->getVenueStatistics(),
            'matches' => $this->getMatchStatistics(),
            'players' => $this->getPlayerStatistics(),
            'teams' => $this->getTeamStatistics(),
            'roles' => $this->getRoleStatistics(),
            'permissions' => $this->getPermissionStatistics(),
        ];

        return $statistics;
    }

    /**
     * Get user statistics from auth service
     *
     * @return array
     */
    private function getUserStatistics(): array
    {
        try {
            $totalUsers = User::count();

            return [
                'total' => $totalUsers,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting user statistics: ' . $e->getMessage());
            return ['total' => 0, 'error' => 'Unable to retrieve user statistics'];
        }
    }

    /**
     * Get tournament statistics from tournament service
     *
     * @return array
     */
    private function getTournamentStatistics(): array
    {
        try {
            $tournamentServiceUrl = env('TOURNAMENT_SERVICE_URL', 'http://tournament-service:8002');
            $token = request()->bearerToken();

            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];

            if ($token) {
                $headers['Authorization'] = 'Bearer ' . $token;
            }

            // Get all tournaments
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->get("{$tournamentServiceUrl}/api/tournaments", [
                    'per_page' => 1, // Just need the total count
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $pagination = $data['pagination'] ?? [];
                $totalTournaments = $pagination['total'] ?? 0;

                // Get active tournaments count
                $activeResponse = Http::timeout(10)
                    ->withHeaders($headers)
                    ->get("{$tournamentServiceUrl}/api/tournaments", [
                        'status' => 'ongoing',
                        'per_page' => 1,
                    ]);

                $activeCount = 0;
                if ($activeResponse->successful()) {
                    $activeData = $activeResponse->json();
                    $activePagination = $activeData['pagination'] ?? [];
                    $activeCount = $activePagination['total'] ?? 0;
                }

                return [
                    'total' => $totalTournaments,
                    'active' => $activeCount,
                ];
            }

            return ['total' => 0, 'active' => 0, 'error' => 'Unable to retrieve tournament statistics'];
        } catch (\Exception $e) {
            Log::error('Error getting tournament statistics: ' . $e->getMessage());
            return ['total' => 0, 'active' => 0, 'error' => 'Unable to retrieve tournament statistics'];
        }
    }

    /**
     * Get sport statistics from tournament service
     *
     * @return array
     */
    private function getSportStatistics(): array
    {
        try {
            $tournamentServiceUrl = env('TOURNAMENT_SERVICE_URL', 'http://tournament-service:8002');

            // Use public endpoint which doesn't require authentication
            $response = Http::timeout(10)
                ->get("{$tournamentServiceUrl}/api/public/sports");

            if ($response->successful()) {
                $data = $response->json();

                // Public endpoint returns: { "success": true, "data": [...] }
                if (isset($data['data']) && is_array($data['data'])) {
                    $total = count($data['data']);
                    return [
                        'total' => $total,
                    ];
                }

                // Fallback: check for pagination
                $pagination = $data['pagination'] ?? [];
                $total = $pagination['total'] ?? 0;

                return [
                    'total' => $total,
                ];
            }

            Log::warning('Failed to retrieve sport statistics', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return ['total' => 0, 'error' => 'Unable to retrieve sport statistics'];
        } catch (\Exception $e) {
            Log::error('Error getting sport statistics: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return ['total' => 0, 'error' => 'Unable to retrieve sport statistics'];
        }
    }

    /**
     * Get venue statistics from tournament service
     *
     * @return array
     */
    private function getVenueStatistics(): array
    {
        try {
            $tournamentServiceUrl = env('TOURNAMENT_SERVICE_URL', 'http://tournament-service:8002');

            // Use public endpoint which doesn't require authentication
            $response = Http::timeout(10)
                ->get("{$tournamentServiceUrl}/api/public/venues");

            if ($response->successful()) {
                $data = $response->json();

                // Public endpoint returns: { "success": true, "data": [...] }
                if (isset($data['data']) && is_array($data['data'])) {
                    $total = count($data['data']);
                    return [
                        'total' => $total,
                    ];
                }

                // Fallback: check for pagination
                $pagination = $data['pagination'] ?? [];
                $total = $pagination['total'] ?? 0;

                return [
                    'total' => $total,
                ];
            }

            Log::warning('Failed to retrieve venue statistics', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return ['total' => 0, 'error' => 'Unable to retrieve venue statistics'];
        } catch (\Exception $e) {
            Log::error('Error getting venue statistics: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return ['total' => 0, 'error' => 'Unable to retrieve venue statistics'];
        }
    }

    /**
     * Get match statistics from match service
     *
     * @return array
     */
    private function getMatchStatistics(): array
    {
        try {
            $matchServiceUrl = env('MATCH_SERVICE_URL', 'http://match-service:8004');

            // Use public endpoint - no authentication required
            $response = Http::timeout(10)
                ->get("{$matchServiceUrl}/api/public/matches", [
                    'per_page' => 1,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Check response structure - ApiResponse::paginated returns:
                // { "success": true, "data": [...], "pagination": {...} }
                if (isset($data['pagination']) && is_array($data['pagination'])) {
                    $pagination = $data['pagination'];
                    $total = $pagination['total'] ?? 0;
                } elseif (isset($data['data']) && is_array($data['data'])) {
                    // Fallback: count items if no pagination
                    $total = count($data['data']);
                } else {
                    // Try to find total in response
                    $total = $data['total'] ?? 0;
                }

                return [
                    'total' => $total,
                ];
            }

            Log::warning('Failed to retrieve match statistics', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return ['total' => 0, 'error' => 'Unable to retrieve match statistics'];
        } catch (\Exception $e) {
            Log::error('Error getting match statistics: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return ['total' => 0, 'error' => 'Unable to retrieve match statistics'];
        }
    }

    /**
     * Get player statistics from team service
     * Since there's no public endpoint to list all players, we aggregate from tournaments
     *
     * @return array
     */
    private function getPlayerStatistics(): array
    {
        try {
            $tournamentServiceUrl = env('TOURNAMENT_SERVICE_URL', 'http://tournament-service:8002');
            $teamServiceUrl = env('TEAM_SERVICE_URL', 'http://team-service:8003');

            // Get all tournaments
            $tournamentsResponse = Http::timeout(10)
                ->get("{$tournamentServiceUrl}/api/tournaments", [
                    'per_page' => 100, // Get more tournaments at once
                ]);

            if (!$tournamentsResponse->successful()) {
                Log::warning('Failed to fetch tournaments for player statistics', [
                    'status' => $tournamentsResponse->status()
                ]);
                return ['total' => 0, 'error' => 'Unable to retrieve player statistics'];
            }

            $tournamentsData = $tournamentsResponse->json();
            $tournaments = $tournamentsData['data'] ?? [];

            if (empty($tournaments)) {
                return ['total' => 0];
            }

            // Aggregate unique players from all tournament teams
            $uniquePlayers = [];

            foreach ($tournaments as $tournament) {
                $tournamentId = $tournament['id'] ?? null;
                if (!$tournamentId) {
                    continue;
                }

                // Get teams for this tournament (public endpoint)
                $teamsResponse = Http::timeout(10)
                    ->get("{$teamServiceUrl}/api/public/tournaments/{$tournamentId}/teams", [
                        'per_page' => 100,
                    ]);

                if (!$teamsResponse->successful()) {
                    continue;
                }

                $teamsData = $teamsResponse->json();
                // Response structure: { "success": true, "data": { "data": [...], "pagination": {...} } }
                $teams = $teamsData['data']['data'] ?? [];

                // Get players from each team's squad
                foreach ($teams as $team) {
                    $teamId = $team['id'] ?? null;
                    if (!$teamId) {
                        continue;
                    }

                    // Get team players (public endpoint) - /api/public/teams/{id}/players
                    // Use per_page=100 to get all players at once
                    $playersResponse = Http::timeout(10)
                        ->get("{$teamServiceUrl}/api/public/teams/{$teamId}/players", [
                            'per_page' => 100,
                        ]);

                    if ($playersResponse->successful()) {
                        $playersData = $playersResponse->json();
                        // Response structure: { "success": true, "data": { "data": [...], "pagination": {...} } }
                        $players = $playersData['data']['data'] ?? [];

                        if (is_array($players)) {
                            foreach ($players as $player) {
                                $playerId = $player['id'] ?? null;
                                if ($playerId) {
                                    $uniquePlayers[$playerId] = true;
                                }
                            }
                        }
                    }
                }
            }

            return [
                'total' => count($uniquePlayers),
            ];
        } catch (\Exception $e) {
            Log::error('Error getting player statistics: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return ['total' => 0, 'error' => 'Unable to retrieve player statistics'];
        }
    }

    /**
     * Get team statistics from team service
     * Since there's no public endpoint to list all teams, we aggregate from tournaments
     *
     * @return array
     */
    private function getTeamStatistics(): array
    {
        try {
            $tournamentServiceUrl = env('TOURNAMENT_SERVICE_URL', 'http://tournament-service:8002');
            $teamServiceUrl = env('TEAM_SERVICE_URL', 'http://team-service:8003');

            // Get all tournaments
            $tournamentsResponse = Http::timeout(10)
                ->get("{$tournamentServiceUrl}/api/tournaments", [
                    'per_page' => 100, // Get more tournaments at once
                ]);

            if (!$tournamentsResponse->successful()) {
                Log::warning('Failed to fetch tournaments for team statistics', [
                    'status' => $tournamentsResponse->status()
                ]);
                return ['total' => 0, 'error' => 'Unable to retrieve team statistics'];
            }

            $tournamentsData = $tournamentsResponse->json();
            $tournaments = $tournamentsData['data'] ?? [];

            if (empty($tournaments)) {
                return ['total' => 0];
            }

            // Aggregate unique teams from all tournaments
            $uniqueTeams = [];

            foreach ($tournaments as $tournament) {
                $tournamentId = $tournament['id'] ?? null;
                if (!$tournamentId) {
                    continue;
                }

                // Get teams for this tournament (public endpoint)
                $teamsResponse = Http::timeout(10)
                    ->get("{$teamServiceUrl}/api/public/tournaments/{$tournamentId}/teams", [
                        'per_page' => 100,
                    ]);

                if ($teamsResponse->successful()) {
                    $teamsData = $teamsResponse->json();
                    // Response structure: { "success": true, "data": { "data": [...], "pagination": {...} } }
                    $teams = $teamsData['data']['data'] ?? [];

                    foreach ($teams as $team) {
                        $teamId = $team['id'] ?? null;
                        if ($teamId) {
                            $uniqueTeams[$teamId] = true;
                        }
                    }
                }
            }

            return [
                'total' => count($uniqueTeams),
            ];
        } catch (\Exception $e) {
            Log::error('Error getting team statistics: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);
            return ['total' => 0, 'error' => 'Unable to retrieve team statistics'];
        }
    }

    /**
     * Get role statistics from auth service
     *
     * @return array
     */
    private function getRoleStatistics(): array
    {
        try {
            $totalRoles = Role::count();

            return [
                'total' => $totalRoles,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting role statistics: ' . $e->getMessage());
            return ['total' => 0, 'error' => 'Unable to retrieve role statistics'];
        }
    }

    /**
     * Get permission statistics from auth service
     *
     * @return array
     */
    private function getPermissionStatistics(): array
    {
        try {
            $totalPermissions = Permission::count();

            return [
                'total' => $totalPermissions,
            ];
        } catch (\Exception $e) {
            Log::error('Error getting permission statistics: ' . $e->getMessage());
            return ['total' => 0, 'error' => 'Unable to retrieve permission statistics'];
        }
    }
}
