@extends('public.layouts.app')

@section('title', $tournament->name . ' - Tournament Details')

@section('content')
<!-- Tournament Header -->
@include('public.partials.page-header', [
    'title' => $tournament->name,
    'subtitle' => 'Tournament Overview and Information'
])

<!-- Tournament Navigation -->
@include('public.partials.tournament-tabs', ['tournament' => $tournament])

<!-- Tournament Overview -->
<section class="py-12">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-8">
                    <h2 class="text-2xl font-bold mb-6">Tournament Information</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-2">Sport</h3>
                            <p class="text-gray-600">{{ $tournament->sport->name ?? 'Not specified' }}</p>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-2">Location</h3>
                            <p class="text-gray-600">{{ $tournament->location ?? 'To be announced' }}</p>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-2">Start Date</h3>
                            <p class="text-gray-600">{{ $tournament->start_date->format('F j, Y') }}</p>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-2">End Date</h3>
                            <p class="text-gray-600">{{ $tournament->end_date->format('F j, Y') }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-primary mb-2">{{ $tournament->teams->count() }}</div>
                            <div class="text-gray-600">Teams</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-success mb-2">{{ $tournament->matches->count() ?? 0 }}</div>
                            <div class="text-gray-600">Matches</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h3 class="text-lg font-bold mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="{{ route('tournaments.matches', $tournament) }}" class="block w-full text-center bg-primary text-white py-2 rounded-lg hover:bg-blue-600 transition">
                            <i class="fas fa-futbol mr-2"></i>View Matches
                        </a>
                        <a href="{{ route('tournaments.teams', $tournament) }}" class="block w-full text-center bg-success text-white py-2 rounded-lg hover:bg-green-600 transition">
                            <i class="fas fa-users mr-2"></i>View Teams
                        </a>
                        <a href="{{ route('tournaments.standings', $tournament) }}" class="block w-full text-center bg-accent text-white py-2 rounded-lg hover:bg-yellow-600 transition">
                            <i class="fas fa-trophy mr-2"></i>View Standings
                        </a>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-bold mb-4">Tournament Status</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Status</span>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full
                                {{ $tournament->start_date <= now() && $tournament->end_date >= now() ? 'bg-success text-white' :
                                   ($tournament->start_date > now() ? 'bg-accent text-white' :
                                   'bg-gray-200 text-gray-700') }}">
                                {{ $tournament->start_date <= now() && $tournament->end_date >= now() ? 'Active' :
                                   ($tournament->start_date > now() ? 'Upcoming' : 'Completed') }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Duration</span>
                            <span class="font-semibold">{{ $tournament->start_date->diffInDays($tournament->end_date) }} days</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Recent Matches -->
<section class="py-6 bg-gray-50">
    <div class="container mx-auto px-4">
        <h2 class="text-2xl font-bold text-center mb-8">Recent Matches</h2>
        @if(isset($recentMatches) && $recentMatches->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                 @foreach($recentMatches as $match)
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <!-- Match Header -->
                        <div class="p-4">
                            <div class="flex justify-between items-center text-white">
                                <div class="text-sm opacity-90">Round {{ $match->round_number }}</div>
                                <div class="px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $match->status === 'completed' ? 'bg-success text-white' :
                                       ($match->status === 'in_progress' ? 'bg-accent text-white' :
                                       ($match->status === 'cancelled' ? 'bg-danger text-white' :
                                       'bg-primary text-white')) }}">
                                    {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                                </div>
                            </div>
                        </div>

                        <!-- Match Content -->
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <div class="text-center flex-1">
                                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                        <span class="text-primary font-bold">{{ substr($match->homeTeam->name, 0, 1) }}</span>
                                    </div>
                                    <div class="font-semibold">{{ $match->homeTeam->name }}</div>
                                </div>

                                <div class="px-4">
                                    <div class="text-2xl font-bold text-center">
                                        {{ $match->home_score ?? '-' }} : {{ $match->away_score ?? '-' }}
                                    </div>
                                    <div class="text-xs text-center text-gray-500 mt-1">
                                        {{ $match->match_date->format('M j, H:i') }}
                                    </div>
                                </div>

                                <div class="text-center flex-1">
                                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                        <span class="text-primary font-bold">{{ substr($match->awayTeam->name, 0, 1) }}</span>
                                    </div>
                                    <div class="font-semibold">{{ $match->awayTeam->name }}</div>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                                <span><i class="fas fa-map-marker-alt mr-1"></i>{{ $match->venue->name ?? 'TBD' }}</span>
                                <span><i class="far fa-clock mr-1"></i>{{ $match->match_date->format('H:i') }}</span>
                            </div>

                            @if($match->status === 'in_progress')
                                <a href="{{ route('matches.live', $match) }}" class="block w-full text-center bg-red-600 text-white py-2 rounded-lg hover:bg-red-700 transition font-semibold mb-2">
                                    <i class="fas fa-video mr-2"></i>Watch Live
                                </a>
                            @endif
                            <a href="{{ route('matches.show', $match) }}" class="block w-full text-center bg-primary text-white py-2 rounded-lg hover:bg-blue-600 transition">
                                View Match Details
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            @include('public.partials.empty-state', [
                'icon' => 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
                'title' => 'No matches found',
                'message' => 'No matches have been scheduled yet.'
            ])
        @endif
    </div>
</section>

<!-- Current Standings -->
<section class="py-12">
    <div class="container mx-auto px-4">
        <h2 class="text-2xl font-bold text-center mb-8">Current Standings</h2>
        @if(isset($standings) && $standings->count() > 0)
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">P</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">W</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">D</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">L</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">GD</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Pts</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($standings->take(5) as $standing)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-medium {{ $standing->position <= 3 ? 'text-lg font-bold text-primary' : '' }}">
                                            {{ $standing->position }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center mr-3">
                                                <span class="text-white font-bold text-sm">{{ substr($standing->team->name, 0, 1) }}</span>
                                            </div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $standing->team->name }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">{{ $standing->played }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-medium text-success">{{ $standing->won }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">{{ $standing->drawn }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-medium text-danger">{{ $standing->lost }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-medium">
                                        <span class="{{ $standing->goal_difference > 0 ? 'text-success' : ($standing->goal_difference < 0 ? 'text-danger' : 'text-gray-600') }}">
                                            {{ $standing->goal_difference > 0 ? '+' : '' }}{{ $standing->goal_difference }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold text-primary">{{ $standing->points }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="text-center p-4">
                    <a href="{{ route('tournaments.standings', $tournament) }}" class="text-primary hover:text-primary-800 font-semibold">
                        View Full Standings <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        @else
            @include('public.partials.empty-state', [
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'title' => 'No standings available',
                'message' => 'Standings will be available once matches are completed.'
            ])
        @endif
    </div>
</section>
@endsection
