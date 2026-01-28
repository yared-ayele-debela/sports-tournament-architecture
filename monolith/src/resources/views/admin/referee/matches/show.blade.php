@extends('layouts.admin')

@section('title', 'Match Management - ' . $match->homeTeam->name . ' vs ' . $match->awayTeam->name)

@section('content')
<div class="max-w-8xl mx-auto">
    <!-- Match Header -->
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Match Management</h1>
                    <p class="text-blue-100">{{ $match->homeTeam->name }} vs {{ $match->awayTeam->name }}</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="font-medium">{{ Auth::user()->name }}</span>
                    <span class="mx-2">•</span>
                    <span>Referee</span>
                </div>
        </div>
    </div>

     <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="grid lg:grid-cols-3 gap-8">
            <!-- Match Info & Controls -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Match Details -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Match Details</h2>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <div class="text-sm text-gray-600 mb-2">Tournament</div>
                            <div class="font-medium">{{ $match->tournament->name }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600 mb-2">Venue</div>
                            <div class="font-medium">{{ $match->venue->name ?? 'TBD' }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600 mb-2">Date & Time</div>
                            <div class="font-medium">{{ $match->match_date->format('M j, Y H:i') }}</div>
                        </div>
                        <div>
                            <div class="text-sm text-gray-600 mb-2">Status</div>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full 
                                {{ $match->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                   ($match->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 
                                   ($match->status === 'cancelled' ? 'bg-red-100 text-red-800' : 
                                   'bg-blue-100 text-blue-800')) }}">
                                {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Score Display -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Current Score</h2>
                    
                    <div class="flex items-center justify-between">
                        <div class="text-center flex-1">
                            <div class="text-sm text-gray-600 mb-2">{{ $match->homeTeam->name }}</div>
                            <div class="text-4xl font-bold text-gray-900">{{ $match->home_score ?? 0 }}</div>
                        </div>
                        
                        <div class="px-8">
                            <div class="text-3xl font-bold text-gray-500">:</div>
                        </div>
                        
                        <div class="text-center flex-1">
                            <div class="text-sm text-gray-600 mb-2">{{ $match->awayTeam->name }}</div>
                            <div class="text-4xl font-bold text-gray-900">{{ $match->away_score ?? 0 }}</div>
                        </div>
                    </div>
                    
                    @if($match->current_minute !== null)
                        <div class="text-center mt-4">
                            <span class="px-4 py-2 bg-blue-100 text-blue-800 rounded-full font-medium">
                                Minute: {{ $match->current_minute }}
                            </span>
                        </div>
                    @endif
                </div>

                <!-- Match Controls -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Match Controls</h2>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @if($match->status === 'scheduled')
                            <form action="{{ route('admin.referee.matches.start', $match) }}" method="POST">
                                @csrf
                                <button type="submit" 
                                        class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                                    Start Match
                                </button>
                            </form>
                        @endif
                        
                        @if($match->status === 'in_progress')
                            <form action="{{ route('admin.referee.matches.pause', $match) }}" method="POST">
                                @csrf
                                <button type="submit" 
                                        class="w-full px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700 transition-colors">
                                    Pause Match
                                </button>
                            </form>
                            
                            <form action="{{ route('admin.referee.matches.end', $match) }}" method="POST">
                                @csrf
                                <button type="submit" 
                                        class="w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors">
                                    End Match
                                </button>
                            </form>
                        @endif
                        
                        @if($match->status !== 'completed')
                            <button onclick="showUpdateMinuteModal()" 
                                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                Update Minute
                            </button>
                        @endif
                        
                        @if($match->status !== 'completed')
                            <button onclick="showUpdateScoreModal()" 
                                    class="w-full px-4 py-2 bg-purple-600 text-white rounded-md hover:bg-purple-700 transition-colors">
                                Update Score
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Event Timeline -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-800">Event Timeline</h2>
                        <a href="{{ route('admin.referee.events.index', $match) }}" 
                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View All Events
                        </a>
                    </div>
                    
                    @if($match->matchEvents->count() > 0)
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            @foreach($match->matchEvents as $event)
                                <div class="flex items-start space-x-3 p-3 rounded-lg 
                                    @if($event->event_type === 'goal')
                                        bg-green-50
                                    @elseif($event->event_type === 'yellow_card')
                                        bg-yellow-50
                                    @elseif($event->event_type === 'red_card')
                                        bg-red-50
                                    @elseif($event->event_type === 'substitution')
                                        bg-blue-50
                                    @else
                                        bg-gray-50
                                    @endif">
                                    <div class="flex-shrink-0 w-16 text-sm font-medium text-gray-900">
                                        {{ $event->minute }}'
                                    </div>
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center space-x-2">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                                    @if($event->event_type === 'goal')
                                                        bg-green-100 text-green-800
                                                    @elseif($event->event_type === 'yellow_card')
                                                        bg-yellow-100 text-yellow-800
                                                    @elseif($event->event_type === 'red_card')
                                                        bg-red-100 text-red-800
                                                    @elseif($event->event_type === 'substitution')
                                                        bg-blue-100 text-blue-800
                                                    @else
                                                        bg-gray-100 text-gray-800
                                                    @endif">
                                                    @if($event->event_type === 'goal')
                                                        ⚽ Goal
                                                    @elseif($event->event_type === 'yellow_card')
                                                        🟨 Yellow Card
                                                    @elseif($event->event_type === 'red_card')
                                                        🟥 Red Card
                                                    @elseif($event->event_type === 'substitution')
                                                        🔄 Substitution
                                                    @else
                                                        📝 Event
                                                    @endif
                                                </span>
                                                <span class="text-sm text-gray-900">
                                                    {{ $event->team->name }}
                                                </span>
                                                @if($event->player)
                                                    <span class="text-sm text-gray-600">
                                                        ({{ $event->player->name }})
                                                    </span>
                                                @endif
                                            </div>
                                            <div class="flex space-x-2">
                                                <form action="{{ route('admin.referee.events.update', [$match, $event]) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" 
                                                            class="text-blue-600 hover:text-blue-800 text-sm">
                                                        Edit
                                                    </button>
                                                </form>
                                                <form action="{{ route('admin.referee.events.destroy', [$match, $event]) }}" method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="text-red-600 hover:text-red-800 text-sm"
                                                            onclick="return confirm('Are you sure you want to delete this event?')">
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        @if($event->description)
                                            <div class="text-sm text-gray-600 mt-1">
                                                {{ $event->description }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            No events recorded yet.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Event Forms -->
            <div class="space-y-6">
                <!-- Add Event Form -->
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Add Event</h2>
                    
                    <form action="{{ route('admin.referee.events.store', $match) }}" method="POST">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Event Type</label>
                                <select name="event_type" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Event Type</option>
                                    <option value="goal">⚽ Goal</option>
                                    <option value="yellow_card">🟨 Yellow Card</option>
                                    <option value="red_card">🟥 Red Card</option>
                                    <option value="substitution">🔄 Substitution</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Team</label>
                                <select name="team_id" required id="teamSelect"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Team</option>
                                    <option value="{{ $match->home_team_id }}" data-players='@json($match->homeTeam->players ?? [])'>{{ $match->homeTeam->name }}</option>
                                    <option value="{{ $match->away_team_id }}" data-players='@json($match->awayTeam->players ?? [])'>{{ $match->awayTeam->name }}</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Player</label>
                                <select name="player_id" id="playerSelect"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select Team First</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Minute</label>
                                <input type="number" name="minute" required min="1" max="120"
                                       placeholder="Enter match minute"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
                                <textarea name="description" rows="3"
                                          placeholder="Add event details..."
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                            </div>
                            
                            <button type="submit" 
                                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                Add Event
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    @include('admin.referee.partials.modals')
</div>

<script>
// Dynamic player loading based on team selection
document.addEventListener('DOMContentLoaded', function() {
    const teamSelect = document.getElementById('teamSelect');
    const playerSelect = document.getElementById('playerSelect');
    
    if (teamSelect && playerSelect) {
        teamSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const players = JSON.parse(selectedOption.getAttribute('data-players') || '[]');
            
            // Clear current player options
            playerSelect.innerHTML = '<option value="">Select Player (Optional)</option>';
            
            // Add players for selected team
            players.forEach(function(player) {
                const option = document.createElement('option');
                option.value = player.id;
                let playerText = player.full_name || 'Player ' + player.id;
                if (player.jersey_number) playerText += ' (#' + player.jersey_number + ')';
                if (player.position) playerText += ' - ' + player.position;
                option.textContent = playerText;
                playerSelect.appendChild(option);
            });
        });
    }
});

function showUpdateMinuteModal() {
    // Simple modal implementation - you can enhance this
    const minute = prompt('Enter current minute (0-120):', '{{ $match->current_minute ?? 0 }}');
    if (minute !== null && !isNaN(minute)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('admin.referee.matches.update-minute', $match) }}';
        
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = '{{ csrf_token() }}';
        form.appendChild(csrf);
        
        const minuteInput = document.createElement('input');
        minuteInput.type = 'hidden';
        minuteInput.name = 'current_minute';
        minuteInput.value = minute;
        form.appendChild(minuteInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function showUpdateScoreModal() {
    const homeScore = prompt('Enter home score:', '{{ $match->home_score ?? 0 }}');
    const awayScore = prompt('Enter away score:', '{{ $match->away_score ?? 0 }}');
    
    if (homeScore !== null && awayScore !== null && !isNaN(homeScore) && !isNaN(awayScore)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('admin.referee.matches.update-score', $match) }}';
        
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = '{{ csrf_token() }}';
        form.appendChild(csrf);
        
        const homeInput = document.createElement('input');
        homeInput.type = 'hidden';
        homeInput.name = 'home_score';
        homeInput.value = homeScore;
        form.appendChild(homeInput);
        
        const awayInput = document.createElement('input');
        awayInput.type = 'hidden';
        awayInput.name = 'away_score';
        awayInput.value = awayScore;
        form.appendChild(awayInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
@endsection
