@extends('layouts.admin')

@section('title','Dashboard')

@section('content')
<div class="max-w-10xl mx-auto">
    <!-- Page Header -->
    <x-ui.page-header
        title="Dashboard"
        subtitle="Overview of your tournament management system"
    />

    <!-- Summary Cards -->
    <div class="grid grid-cols-3 md:grid-cols-3 lg:grid-cols-4 gap-6 mb-8">
        <x-ui.stat-card
            title="Total Tournaments"
            :value="$stats['tournaments']"
            icon="fas fa-trophy"
            icon-color="indigo"
        />

        <x-ui.stat-card
            title="Active Tournaments"
            :value="$stats['active_tournaments']"
            icon="fas fa-check-circle"
            icon-color="green"
        />

        <x-ui.stat-card
            title="Total Matches"
            :value="$stats['matches']"
            icon="fas fa-calendar-alt"
            icon-color="blue"
        />

        <x-ui.stat-card
            title="Completed Matches"
            :value="$stats['completed_matches']"
            icon="fas fa-check-double"
            icon-color="purple"
        />

        <x-ui.stat-card
            title="Total Teams"
            :value="$stats['teams']"
            icon="fas fa-users"
            icon-color="orange"
        />

        <x-ui.stat-card
            title="Total Users"
            :value="$stats['users']"
            icon="fas fa-user-friends"
            icon-color="teal"
        />

        <x-ui.stat-card
            title="Referees"
            :value="$stats['referees']"
            icon="fas fa-user-shield"
            icon-color="red"
        />

        <x-ui.stat-card
            title="Venues"
            :value="$stats['venues']"
            icon="fas fa-map-marker-alt"
            icon-color="yellow"
        />
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <x-ui.card title="Match Status Distribution" icon="fas fa-chart-pie">
            <div class="relative h-64">
                <canvas id="matchStatusChart" height="400"></canvas>
            </div>
        </x-ui.card>

        <x-ui.card title="Matches Per Day (Last 7 Days)" icon="fas fa-chart-line">
            <div class="relative h-64">
                <canvas id="dailyMatchesChart" height="400"></canvas>
            </div>
        </x-ui.card>
    </div>

    <!-- Recent Activity Panels -->
    <div class="grid grid-cols- lg:grid-cols-2 gap-6">
        <x-ui.card title="Recent Matches" icon="fas fa-clock">
            <div class="space-y-3">
                @forelse ($recentMatches as $match)
                    <a href="{{ route('admin.matches.show', $match['id']) }}" class="flex items-start justify-between py-2 border-b border-gray-100 last:border-0">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ $match['teams'] }}</p>
                            <div class="flex items-center mt-1">
                                <x-ui.badge :variant="str_replace('_', '-', $match['status']) === 'completed' ? 'success' : (str_replace('_', '-', $match['status']) === 'in-progress' ? 'warning' : 'info')">
                                    {{ ucfirst(str_replace('_', ' ', $match['status'])) }}
                                </x-ui.badge>
                            </div>
                        </div>
                        <span class="text-xs text-gray-500 ml-4">{{ $match['created_at'] }}</span>
                    </a>
                @empty
                    <div class="text-center py-8">
                        <i class="fas fa-inbox text-3xl text-gray-300 mb-2"></i>
                        <p class="text-sm text-gray-500">No recent matches</p>
                    </div>
                @endforelse
            </div>
        </x-ui.card>



        <x-ui.card title="Recent Completed Matches" icon="fas fa-check-double">
            <div class="space-y-3">
                @forelse ($recentCompletedMatches as $match)
                    <a href="{{ route('admin.matches.show', $match['id']) }}" class="flex items-start justify-between py-2 border-b border-gray-100 last:border-0">
                        <div class="flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ $match['teams'] }}</p>
                            <p class="text-sm font-semibold text-indigo-600 mt-1">{{ $match['score'] }}</p>
                        </div>
                        <span class="text-xs text-gray-500 ml-4">{{ $match['completed_at'] }}</span>
                    </a>
                @empty
                    <div class="text-center py-8">
                        <i class="fas fa-inbox text-3xl text-gray-300 mb-2"></i>
                        <p class="text-sm text-gray-500">No completed matches</p>
                    </div>
                @endforelse
            </div>
        </x-ui.card>
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
