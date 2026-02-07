@extends('public.layouts.app')

@section('title', $match->homeTeam->name . ' vs ' . $match->awayTeam->name . ' - Match Details')

@section('content')

    <!-- Match Header -->
    <section class="bg-gradient-to-r from-primary to-blue-600 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="text-center mb-6">
                <div class="inline-block px-3 py-1 bg-white bg-opacity-20 rounded-full text-sm font-semibold mb-4">
                    {{ $match->tournament->name }}
                </div>
                <h1 class="text-4xl font-bold mb-4">Match Details</h1>
            </div>

            <div class="bg-white bg-opacity-10 rounded-2xl p-8 backdrop-blur-sm">
                <div class="flex items-center justify-between">
                    <div class="text-center flex-1">
                        <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-3">
                            <span class="text-3xl font-bold">{{ substr($match->homeTeam->name, 0, 1) }}</span>
                        </div>
                        <h3 class="text-xl font-bold">{{ $match->homeTeam->name }}</h3>
                        <div class="text-sm opacity-75">Home</div>
                    </div>

                    <div class="px-8">
                        <div class="text-5xl font-bold text-center">
                            {{ $match->home_score ?? '-' }} : {{ $match->away_score ?? '-' }}
                        </div>
                        <div class="text-center mt-2">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full
                                {{ $match->status === 'completed' ? 'bg-success text-white' :
                                   ($match->status === 'in_progress' ? 'bg-accent text-white' :
                                   ($match->status === 'cancelled' ? 'bg-danger text-white' :
                                   'bg-white text-primary')) }}">
                                {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                            </span>
                        </div>
                    </div>

                    <div class="text-center flex-1">
                        <div class="w-20 h-20 bg-white bg-opacity-20 rounded-full flex items-center justify-center mx-auto mb-3">
                            <span class="text-3xl font-bold">{{ substr($match->awayTeam->name, 0, 1) }}</span>
                        </div>
                        <h3 class="text-xl font-bold">{{ $match->awayTeam->name }}</h3>
                        <div class="text-sm opacity-75">Away</div>
                    </div>
                </div>

                <div class="flex justify-center items-center gap-6 mt-6 text-sm">
                    <span><i class="far fa-calendar mr-2"></i>{{ $match->match_date->format('M j, Y') }}</span>
                    <span><i class="far fa-clock mr-2"></i>{{ $match->match_date->format('H:i') }}</span>
                    <span><i class="fas fa-map-marker-alt mr-2"></i>{{ $match->venue->name ?? 'TBD' }}</span>
                    <span><i class="fas fa-tag mr-2"></i>Round {{ $match->round_number }}</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Match Content -->
    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="grid lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-8">
                    <!-- Match Statistics (Only show if match has events) -->
                    @php
                        $homeEvents = $match->matchEvents->where('team_id', $match->home_team_id);
                        $awayEvents = $match->matchEvents->where('team_id', $match->away_team_id);
                    @endphp
                    @if($match->matchEvents->count() > 0)
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-2xl font-bold mb-6">Match Statistics</h2>
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <h3 class="font-semibold mb-4">{{ $match->homeTeam->name }}</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Goals</span>
                                        <span class="font-semibold">{{ $homeEvents->where('event_type', 'goal')->count() }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Yellow Cards</span>
                                        <span class="font-semibold">{{ $homeEvents->where('event_type', 'yellow_card')->count() }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Red Cards</span>
                                        <span class="font-semibold">{{ $homeEvents->where('event_type', 'red_card')->count() }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Substitutions</span>
                                        <span class="font-semibold">{{ $homeEvents->where('event_type', 'substitution')->count() }}</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 class="font-semibold mb-4">{{ $match->awayTeam->name }}</h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Goals</span>
                                        <span class="font-semibold">{{ $awayEvents->where('event_type', 'goal')->count() }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Yellow Cards</span>
                                        <span class="font-semibold">{{ $awayEvents->where('event_type', 'yellow_card')->count() }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Red Cards</span>
                                        <span class="font-semibold">{{ $awayEvents->where('event_type', 'red_card')->count() }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Substitutions</span>
                                        <span class="font-semibold">{{ $awayEvents->where('event_type', 'substitution')->count() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Match Events -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-2xl font-bold mb-6">Match Events</h2>
                        @if($match->matchEvents && $match->matchEvents->count() > 0)
                            <div class="space-y-4">
                                @foreach($match->matchEvents->sortBy('minute') as $event)
                                    @php
                                        $eventIcons = [
                                            'goal' => 'fa-futbol',
                                            'yellow_card' => 'fa-square',
                                            'red_card' => 'fa-square',
                                            'substitution' => 'fa-exchange-alt'
                                        ];
                                        $eventColors = [
                                            'goal' => 'bg-green-100 text-green-800',
                                            'yellow_card' => 'bg-yellow-100 text-yellow-800',
                                            'red_card' => 'bg-red-100 text-red-800',
                                            'substitution' => 'bg-blue-100 text-blue-800'
                                        ];
                                        $icon = $eventIcons[$event->event_type] ?? 'fa-circle';
                                        $color = $eventColors[$event->event_type] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <div class="flex items-center gap-4 p-3 bg-gray-50 rounded-lg">
                                        <div class="text-sm font-semibold text-gray-500 w-12">
                                            {{ $event->minute }}'
                                        </div>
                                        <div class="w-8 h-8 {{ $color }} rounded-full flex items-center justify-center">
                                            <i class="fas {{ $icon }} text-xs"></i>
                                        </div>
                                        <div class="flex-1">
                                            <div class="font-semibold capitalize">{{ str_replace('_', ' ', $event->event_type) }}</div>
                                            <div class="text-sm text-gray-600">{{ $event->player ? $event->player->name : 'N/A' }}</div>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $event->team->name }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                No match events recorded yet.
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-8">
                    <!-- Match Info -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-xl font-bold mb-4">Match Information</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-gray-600 text-sm">Tournament</span>
                                <div class="font-semibold">{{ $match->tournament->name }}</div>
                            </div>
                            <div>
                                <span class="text-gray-600 text-sm">Venue</span>
                                <div class="font-semibold">{{ $match->venue->name ?? 'TBD' }}</div>
                            </div>
                            <div>
                                <span class="text-gray-600 text-sm">Date & Time</span>
                                <div class="font-semibold">{{ $match->match_date->format('M j, Y - H:i') }}</div>
                            </div>
                            <div>
                                <span class="text-gray-600 text-sm">Round</span>
                                <div class="font-semibold">Round {{ $match->round_number }}</div>
                            </div>
                            <div>
                                <span class="text-gray-600 text-sm">Status</span>
                                <div class="font-semibold">{{ ucfirst(str_replace('_', ' ', $match->status)) }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-xl font-bold mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            @if($match->status === 'in_progress')
                                <a href="{{ route('matches.live', $match) }}" class="block w-full text-center bg-red-600 text-white py-2 rounded-lg hover:bg-red-700 transition font-semibold">
                                    <i class="fas fa-video mr-2"></i>Watch Live
                                </a>
                            @endif
                            <a href="{{ route('tournaments.show', $match->tournament) }}" class="block w-full text-center bg-primary text-white py-2 rounded-lg hover:bg-blue-600 transition">
                                <i class="fas fa-trophy mr-2"></i>View Tournament
                            </a>
                            <a href="{{ route('teams.show', $match->homeTeam) }}" class="block w-full text-center bg-gray-100 text-gray-700 py-2 rounded-lg hover:bg-gray-200 transition">
                                <i class="fas fa-users mr-2"></i>{{ $match->homeTeam->name }}
                            </a>
                            <a href="{{ route('teams.show', $match->awayTeam) }}" class="block w-full text-center bg-gray-100 text-gray-700 py-2 rounded-lg hover:bg-gray-200 transition">
                                <i class="fas fa-users mr-2"></i>{{ $match->awayTeam->name }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->

@endsection
