@extends('public.layouts.app')

@section('title', 'Live Match - ' . $match->homeTeam->name . ' vs ' . $match->awayTeam->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Match Header -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Live Match</h1>
                <p class="text-gray-600 mt-1">{{ $match->tournament->name ?? 'Tournament' }}</p>
            </div>
            <div class="text-right">
                <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                    <span class="w-2 h-2 bg-red-500 rounded-full mr-2 animate-pulse"></span>
                    LIVE
                </div>
            </div>
        </div>

        <!-- Score Display -->
        <div id="match-score" class="grid grid-cols-3 gap-4 items-center">
            <div class="text-center">
                <div class="text-2xl font-bold" id="home-team-name">{{ $match->homeTeam->name }}</div>
                <div class="text-4xl font-bold text-indigo-600 mt-2" id="home-score">{{ $match->home_score ?? 0 }}</div>
            </div>
            <div class="text-center">
                <div class="text-sm text-gray-500" id="match-status">{{ strtoupper($match->status) }}</div>
                <div class="text-lg font-semibold mt-2" id="current-minute">{{ $match->current_minute ?? 0 }}'</div>
            </div>
            <div class="text-center">
                <div class="text-2xl font-bold" id="away-team-name">{{ $match->awayTeam->name }}</div>
                <div class="text-4xl font-bold text-indigo-600 mt-2" id="away-score">{{ $match->away_score ?? 0 }}</div>
            </div>
        </div>
    </div>

    <!-- Match Events -->
    <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <h2 class="text-2xl font-bold mb-4">Match Events</h2>
        <div id="match-events" class="space-y-3">
            <div class="text-center text-gray-500 py-8">
                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                <p>Loading events...</p>
            </div>
        </div>
    </div>

    <!-- Match Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Home Team Stats -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-bold mb-4" id="home-team-stats-name">{{ $match->homeTeam->name }}</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Goals</span>
                    <span class="font-semibold" id="home-goals">0</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Yellow Cards</span>
                    <span class="font-semibold" id="home-yellow-cards">0</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Red Cards</span>
                    <span class="font-semibold" id="home-red-cards">0</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Substitutions</span>
                    <span class="font-semibold" id="home-substitutions">0</span>
                </div>
            </div>
        </div>

        <!-- Away Team Stats -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-bold mb-4" id="away-team-stats-name">{{ $match->awayTeam->name }}</h3>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Goals</span>
                    <span class="font-semibold" id="away-goals">0</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Yellow Cards</span>
                    <span class="font-semibold" id="away-yellow-cards">0</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Red Cards</span>
                    <span class="font-semibold" id="away-red-cards">0</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Substitutions</span>
                    <span class="font-semibold" id="away-substitutions">0</span>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const matchId = {{ $match->id }};
    let events = [];
    let lastUpdate = null;

    // Event type icons
    const eventIcons = {
        'goal': 'fa-futbol',
        'yellow_card': 'fa-square',
        'red_card': 'fa-square',
        'substitution': 'fa-exchange-alt'
    };

    // Event type colors
    const eventColors = {
        'goal': 'bg-green-100 text-green-800',
        'yellow_card': 'bg-yellow-100 text-yellow-800',
        'red_card': 'bg-red-100 text-red-800',
        'substitution': 'bg-blue-100 text-blue-800'
    };

    // Polling method (stable and reliable)
    function initPolling() {
        console.log('Using polling for live updates');
        fetchMatchData();
        // Poll every 5 seconds to reduce server load
        setInterval(fetchMatchData, 5000);
    }

    // Fetch match data via API
    async function fetchMatchData() {
        try {
            const response = await fetch(`/api/v1/matches/${matchId}/live`);
            const result = await response.json();

            if (result.success && result.data) {
                updateMatchData(result.data);
            } else {
                console.error('API response error:', result);
            }
        } catch (error) {
            console.error('Error fetching match data:', error);
        }
    }

    // Update match data in UI
    function updateMatchData(data) {
        // Update score
        if (data.match) {
            document.getElementById('home-score').textContent = data.match.home_score || 0;
            document.getElementById('away-score').textContent = data.match.away_score || 0;
            document.getElementById('current-minute').textContent = (data.match.current_minute || 0) + "'";
            document.getElementById('match-status').textContent = (data.match.status || 'SCHEDULED').toUpperCase();
        }

        // Update stats
        if (data.stats) {
            document.getElementById('home-goals').textContent = data.stats.home.goals || 0;
            document.getElementById('home-yellow-cards').textContent = data.stats.home.yellow_cards || 0;
            document.getElementById('home-red-cards').textContent = data.stats.home.red_cards || 0;
            document.getElementById('home-substitutions').textContent = data.stats.home.substitutions || 0;

            document.getElementById('away-goals').textContent = data.stats.away.goals || 0;
            document.getElementById('away-yellow-cards').textContent = data.stats.away.yellow_cards || 0;
            document.getElementById('away-red-cards').textContent = data.stats.away.red_cards || 0;
            document.getElementById('away-substitutions').textContent = data.stats.away.substitutions || 0;
        }

        // Update events (always render, not just on length change)
        if (data.events) {
            events = data.events;
            renderEvents(events);
        }
    }

    // Render events list
    function renderEvents(eventsList) {
        const container = document.getElementById('match-events');

        if (!eventsList || eventsList.length === 0) {
            container.innerHTML = '<div class="text-center text-gray-500 py-8">No events yet</div>';
            return;
        }

        container.innerHTML = eventsList.map(event => {
            const icon = eventIcons[event.event_type] || 'fa-circle';
            const color = eventColors[event.event_type] || 'bg-gray-100 text-gray-800';
            const playerName = event.player ? (event.player.name || 'N/A') : 'N/A';
            const teamName = event.team ? (event.team.name || 'Unknown') : 'Unknown';

            return `
                <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="text-sm font-semibold text-gray-500 w-12">${event.minute || 0}'</div>
                    <div class="w-8 h-8 ${color} rounded-full flex items-center justify-center">
                        <i class="fas ${icon} text-xs"></i>
                    </div>
                    <div class="flex-1">
                        <div class="font-semibold capitalize">${(event.event_type || '').replace('_', ' ')}</div>
                        <div class="text-sm text-gray-600">${playerName}</div>
                    </div>
                    <div class="text-sm text-gray-500">${teamName}</div>
                </div>
            `;
        }).join('');
    }

    // Initialize polling on page load
    initPolling();
})();
</script>
@endpush
@endsection
