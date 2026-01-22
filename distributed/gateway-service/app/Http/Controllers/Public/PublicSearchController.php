<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Clients\TeamServiceClient;
use App\Services\Clients\TournamentServiceClient;
use App\Services\Clients\MatchServiceClient;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class PublicSearchController extends Controller
{
    protected TeamServiceClient $teamClient;
    protected TournamentServiceClient $tournamentClient;
    protected MatchServiceClient $matchClient;

    public function __construct(
        TeamServiceClient $teamClient,
        TournamentServiceClient $tournamentClient,
        MatchServiceClient $matchClient
    ) {
        $this->teamClient = $teamClient;
        $this->tournamentClient = $tournamentClient;
        $this->matchClient = $matchClient;
    }

    /**
     * Global search across tournaments, teams, and matches
     */
    public function search(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'q' => 'required|string|min:2|max:100',
                'type' => 'nullable|string|in:all,tournaments,teams,matches',
                'limit' => 'nullable|integer|min:1|max:50',
                'tournament_id' => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validationError($validator->errors());
            }

            $query = $request->get('q');
            $type = $request->get('type', 'all');
            $limit = min($request->get('limit', 20), 50);
            $tournamentId = $request->get('tournament_id');

            $cacheKey = 'search_public:' . md5(serialize($request->only(['q', 'type', 'limit', 'tournament_id'])));
            $cacheTtl = 300; // 5 minutes

            $results = Cache::remember($cacheKey, $cacheTtl, function () use ($query, $type, $limit, $tournamentId) {
                $searchResults = [];

                // Search tournaments
                if ($type === 'all' || $type === 'tournaments') {
                    $tournamentResults = $this->searchTournaments($query, $limit, $tournamentId);
                    if (!empty($tournamentResults)) {
                        $searchResults['tournaments'] = $tournamentResults;
                    }
                }

                // Search teams
                if ($type === 'all' || $type === 'teams') {
                    $teamResults = $this->searchTeams($query, $limit, $tournamentId);
                    if (!empty($teamResults)) {
                        $searchResults['teams'] = $teamResults;
                    }
                }

                // Search matches
                if ($type === 'all' || $type === 'matches') {
                    $matchResults = $this->searchMatches($query, $limit, $tournamentId);
                    if (!empty($matchResults)) {
                        $searchResults['matches'] = $matchResults;
                    }
                }

                return $searchResults;
            });

            return ApiResponse::success($results, 'Search results retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to perform search');
        }
    }

    private function searchTournaments(string $query, int $limit, ?int $tournamentId = null): array
    {
        $filters = [
            'q' => $query,
            'limit' => $limit,
        ];

        if ($tournamentId) {
            $filters['tournament_id'] = $tournamentId;
        }

        $response = $this->tournamentClient->getTournaments($filters);

        if (empty($response['success'])) {
            return [];
        }

        $body = $response['data'] ?? null;
        $items = $body['data'] ?? $body;

        return is_array($items) ? array_slice($items, 0, $limit) : [];
    }

    private function searchTeams(string $query, int $limit, ?int $tournamentId = null): array
    {
        $filters = [
            'limit' => $limit,
        ];

        if ($tournamentId) {
            $filters['tournament_id'] = $tournamentId;
        }

        $response = $this->teamClient->searchTeams($query, $filters);

        if (empty($response['success'])) {
            return [];
        }

        $body = $response['data'] ?? null;
        $items = $body['data'] ?? $body;

        return is_array($items) ? array_slice($items, 0, $limit) : [];
    }

    private function searchMatches(string $query, int $limit, ?int $tournamentId = null): array
    {
        $filters = [
            'q' => $query,
            'limit' => $limit,
        ];

        if ($tournamentId) {
            $filters['tournament_id'] = $tournamentId;
        }

        $response = $this->matchClient->getMatches($filters);

        if (empty($response['success'])) {
            return [];
        }

        $body = $response['data'] ?? null;
        $items = $body['data'] ?? $body;

        return is_array($items) ? array_slice($items, 0, $limit) : [];
    }

}
