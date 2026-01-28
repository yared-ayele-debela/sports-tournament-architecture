@extends('layouts.admin')

@section('title','Dashboard')

@section('content')
<div class="max-w-10xl mx-auto">
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-4 xl:grid-cols-4 gap-6 mb-8">
        <!-- Total Tournaments -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-indigo-100 rounded-lg p-3">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Tournaments</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['tournaments'] }}</p>
                </div>
            </div>
        </div>

        <!-- Active Tournaments -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Active Tournaments</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['active_tournaments'] }}</p>
                </div>
            </div>
        </div>

        <!-- Total Matches -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h18M7 12h.01M7 12h.01M7 12h.01M7 12h.01M7 12h.01M7 12h.01" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Matches</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['matches'] }}</p>
                </div>
            </div>
        </div>

        <!-- Completed Matches -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Completed Matches</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['completed_matches'] }}</p>
                </div>
            </div>
        </div>

        <!-- Total Teams -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-orange-100 rounded-lg p-3">
                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Teams</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['teams'] }}</p>
                </div>
            </div>
        </div>

        <!-- Total Users -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-teal-100 rounded-lg p-3">
                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Users</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['users'] }}</p>
                </div>
            </div>
        </div>

        <!-- Referees Count -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-red-100 rounded-lg p-3">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Referees</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $stats['referees'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Match Status Chart -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Match Status Distribution</h2>
            <div class="relative h-64">
                <canvas id="matchStatusChart" height="400"></canvas>
            </div>
        </div>

        <!-- Matches Per Day Chart -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Matches Per Day (Last 7 Days)</h2>
            <div class="relative h-64">
                <canvas id="dailyMatchesChart" height="400"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Activity Panels -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Matches -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Matches</h2>
            <div class="space-y-3">
                @forelse ($recentMatches as $match)
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ $match['teams'] }}</p>
                            <div class="flex items-center mt-1">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $match['status_badge'] }}">
                                    {{ ucfirst(str_replace('_', ' ', $match['status'])) }}
                                </span>
                            </div>
                        </div>
                        <span class="text-xs text-gray-500">{{ $match['created_at'] }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No recent matches</p>
                @endforelse
            </div>
        </div>

        <!-- Recent Users -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Users</h2>
            <div class="space-y-3">
                @forelse ($recentUsers as $user)
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ $user['name'] }}</p>
                            <p class="text-xs text-gray-500">{{ $user['email'] }}</p>
                        </div>
                        <span class="text-xs text-gray-500">{{ $user['created_at'] }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No recent users</p>
                @endforelse
            </div>
        </div>

        <!-- Recent Completed Matches -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Completed Matches</h2>
            <div class="space-y-3">
                @forelse ($recentCompletedMatches as $match)
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ $match['teams'] }}</p>
                            <p class="text-sm font-semibold text-gray-700 mt-1">{{ $match['score'] }}</p>
                        </div>
                        <span class="text-xs text-gray-500">{{ $match['completed_at'] }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No completed matches</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Match Status Chart - Pie Chart
    const matchStatusCtx = document.getElementById('matchStatusChart').getContext('2d');
    new Chart(matchStatusCtx, {
        type: 'pie',
        data: {
            labels: @json($matchStatusData['labels']),
            datasets: [{
                data: @json($matchStatusData['data']),
                backgroundColor: @json($matchStatusData['colors']),
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 12
                        }
                    }
                }
            }
        }
    });

    // Daily Matches Chart - Line Chart
    const dailyMatchesCtx = document.getElementById('dailyMatchesChart').getContext('2d');
    new Chart(dailyMatchesCtx, {
        type: 'line',
        data: {
            labels: @json($dailyMatchesData['labels']),
            datasets: [{
                label: 'Matches',
                data: @json($dailyMatchesData['data']),
                borderColor: '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#3B82F6',
                pointBorderColor: '#ffffff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    }
                },
                x: {
                    ticks: {
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        display: false
                    }
                }
            }
        }
    });
});
</script>
@endsection