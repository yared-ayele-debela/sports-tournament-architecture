<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\MatchModel;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminDashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }
    public function index()
    {
        $this->checkPermission('view_admin_dashboard');

        // Use service to get all dashboard data
        $cacheTime = 300;
        $stats = $this->dashboardService->getAdminStatistics($cacheTime);
        $matchStatusData = $this->dashboardService->getMatchStatusChartData($cacheTime);
        $dailyMatchesData = $this->dashboardService->getDailyMatchesChartData($cacheTime);
        $recentMatches = $this->dashboardService->getRecentMatches(5, $cacheTime);
        $recentUsers = $this->dashboardService->getRecentUsers(5, $cacheTime);
        $recentCompletedMatches = $this->dashboardService->getRecentCompletedMatches(5, $cacheTime);

        return view('admin.dashboard', compact(
            'stats',
            'matchStatusData',
            'dailyMatchesData',
            'recentMatches',
            'recentUsers',
            'recentCompletedMatches'
        ));
    }

    /**
     * Display the coach dashboard within admin panel.
     */
    public function coachDashboard()
    {
        $this->checkPermission('view_coach_dashboard');

        // Use service to get coach dashboard data
        $dashboardData = $this->dashboardService->getCoachDashboardData(Auth::user());

        return view('admin.coach.dashboard.coach-dashboard', $dashboardData);
    }
}
