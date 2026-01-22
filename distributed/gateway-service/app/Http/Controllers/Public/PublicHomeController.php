<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\Aggregators\TournamentAggregator;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PublicHomeController extends Controller
{
    protected TournamentAggregator $tournamentAggregator;

    public function __construct(TournamentAggregator $tournamentAggregator)
    {
        $this->tournamentAggregator = $tournamentAggregator;
    }

    /**
     * Get home page data with featured tournaments
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Cache the entire home page response
            $cacheKey = 'home_page_data';
            $cacheTtl = 300; // 5 minutes

            $data = Cache::remember($cacheKey, $cacheTtl, function () {
                $featuredTournaments = $this->tournamentAggregator->getFeaturedTournaments();
                
                return [
                    'featured_tournaments' => $featuredTournaments['success'] ? $featuredTournaments['data'] : [],
                    'meta' => [
                        'total_featured' => count($featuredTournaments['success'] ? $featuredTournaments['data'] : []),
                        'last_updated' => now()->toISOString(),
                    ],
                ];
            });

            return ApiResponse::success($data, 'Home page data retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve home page data');
        }
    }

    /**
     * Get quick stats for home page
     */
    public function stats(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $cacheKey = 'home_stats';
            $cacheTtl = 600; // 10 minutes

            $stats = Cache::remember($cacheKey, $cacheTtl, function () {
                // This would typically fetch from various services
                // For now, returning mock data structure
                return [
                    'total_tournaments' => 0,
                    'active_tournaments' => 0,
                    'total_teams' => 0,
                    'total_matches_today' => 0,
                    'live_matches' => 0,
                ];
            });

            return ApiResponse::success($stats, 'Stats retrieved successfully');
        } catch (\Exception $e) {
            return ApiResponse::serverError('Failed to retrieve stats');
        }
    }
}
