@extends('layouts.admin')

@section('title','Coach Dashboard')

@section('content')
<div class="max-w-10xl mx-auto">
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            <div class="flex">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 0 8 8 0 006 0zm-3.707-9.293a1 1 0 00-1.414 1.414L9 10.586 7.707a1 1 0 00-1.414 0l-2 2a1 1 0 001.414 1.414l2 2a1 1 0 001.414 0z" clip-rule="evenodd" />
                </svg>
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <div class="flex">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 0 8 8 0 006 0zm-3.707-9.293a1 1 0 00-1.414 1.414L9 10.586 7.707a1 1 0 00-1.414 0l-2 2a1 1 0 001.414 1.414l2 2a1 1 0 001.414 0z" clip-rule="evenodd" />
                </svg>
                {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- Dashboard Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-indigo-100 rounded-lg p-3">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.356-1.857" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Teams</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $teams->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 8 4 4 0 00-8 0zM4.58 16.017a.5.5 0 00-.196.012L6 18l-.812.812a.5.5 0 00-.688.012L4.58 16.017zM18 8a6 6 0 11-12 0 6 6 0 0112 0zM12 14a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Total Players</p>
                    <p class="text-2xl font-semibold text-gray-900">
                        {{ $teams->sum(function($team) { return $team->players->count(); }) }}
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-100 rounded-lg p-3">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 4h18M8 7l4-4m0 0h18M8 7v8m0 0l4 4" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600">Upcoming Matches</p>
                    <p class="text-2xl font-semibold text-gray-900">{{ $upcomingMatches->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Teams Overview -->
    @if($teams->count() > 0)
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">My Teams</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($teams as $team)
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-center mb-4">
                                @if($team->logo)
                                    <img src="{{ asset('storage/' . $team->logo) }}" alt="{{ $team->name }}" class="w-12 h-12 rounded-full object-cover mr-3">
                                @else
                                    <div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center mr-3">
                                        <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.356-1.857" />
                                        </svg>
                                    </div>
                                @endif
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900">{{ $team->name }}</h4>
                                    <p class="text-sm text-gray-600">{{ $team->tournament->name ?? 'No Tournament' }}</p>
                                </div>
                            </div>
                            <div class="mt-4 text-sm text-gray-600">
                                <p><strong>Players:</strong> {{ $team->players->count() }}</p>
                                <p><strong>Coach:</strong> {{ $team->coach_name ?? 'Not assigned' }}</p>
                            </div>
                            <div class="mt-4">
                                <a href="{{ route('admin.teams.show', $team->id) }}" class="inline-flex items-center px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    View Team
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @else
        <div class="bg-gray-50 rounded-lg p-8 text-center mb-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.356-1.857" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">No Teams Assigned</h3>
            <p class="mt-1 text-gray-600">You haven't been assigned to any teams yet. Contact an administrator to get started.</p>
        </div>
    @endif

    <!-- Match Schedule -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Upcoming Matches -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Upcoming Matches</h3>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                        {{ $upcomingMatches->count() }} matches
                    </span>
                </div>
            </div>
            <div class="p-6 max-h-96 overflow-y-auto">
                @if($upcomingMatches->count() > 0)
                    <div class="space-y-3">
                        @foreach($upcomingMatches as $match)
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="text-sm font-medium text-gray-500">
                                            {{ $match->match_date->format('M d') }}
                                        </div>
                                        <div class="flex items-center">
                                            @if($match->homeTeam->logo)
                                                <img src="{{ asset('storage/' . $match->homeTeam->logo) }}" alt="{{ $match->homeTeam->name }}" class="w-6 h-6 rounded-full object-cover">
                                            @else
                                                <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center">
                                                    <span class="text-xs font-bold text-gray-600">{{ substr($match->homeTeam->name, 0, 1) }}</span>
                                                </div>
                                            @endif
                                            <span class="mx-2 text-sm font-medium">{{ $match->homeTeam->name }}</span>
                                            <span class="text-gray-400">vs</span>
                                            @if($match->awayTeam->logo)
                                                <img src="{{ asset('storage/' . $match->awayTeam->logo) }}" alt="{{ $match->awayTeam->name }}" class="w-6 h-6 rounded-full object-cover">
                                            @else
                                                <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center">
                                                    <span class="text-xs font-bold text-gray-600">{{ substr($match->awayTeam->name, 0, 1) }}</span>
                                                </div>
                                            @endif
                                            <span class="mx-2 text-sm font-medium">{{ $match->awayTeam->name }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-gray-500">{{ $match->match_date->format('H:i') }}</div>
                                        <div class="text-xs text-gray-400">{{ $match->venue->name ?? 'TBD' }}</div>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $match->status === 'scheduled' ? 'bg-blue-100 text-blue-800' : ($match->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : ($match->status === 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800')) }}">
                                            {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 4h18M8 7l4-4m0 0h18M8 7v8m0 0l4 4" />
                        </svg>
                        <h3 class="mt-2 text-lg font-medium text-gray-900">No Upcoming Matches</h3>
                        <p class="mt-1 text-gray-600">Your teams don't have any scheduled matches.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Recent Matches -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900">Recent Matches</h3>
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                        {{ $recentMatches->count() }} matches
                    </span>
                </div>
            </div>
            <div class="p-6 max-h-96 overflow-y-auto">
                @if($recentMatches->count() > 0)
                    <div class="space-y-3">
                        @foreach($recentMatches as $match)
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow {{ $match->status === 'completed' ? 'bg-green-50' : '' }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="text-sm font-medium text-gray-500">
                                            {{ $match->match_date->format('M d') }}
                                        </div>
                                        <div class="flex items-center">
                                            @if($match->homeTeam->logo)
                                                <img src="{{ asset('storage/' . $match->homeTeam->logo) }}" alt="{{ $match->homeTeam->name }}" class="w-6 h-6 rounded-full object-cover">
                                            @else
                                                <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center">
                                                    <span class="text-xs font-bold text-gray-600">{{ substr($match->homeTeam->name, 0, 1) }}</span>
                                                </div>
                                            @endif
                                            <span class="mx-2 text-sm font-medium">{{ $match->homeTeam->name }}</span>
                                            @if($match->status === 'completed')
                                                <span class="text-gray-400">{{ $match->home_score ?? '-' }}</span>
                                            @endif
                                            <span class="text-gray-400">vs</span>
                                            @if($match->awayTeam->logo)
                                                <img src="{{ asset('storage/' . $match->awayTeam->logo) }}" alt="{{ $match->awayTeam->name }}" class="w-6 h-6 rounded-full object-cover">
                                            @else
                                                <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center">
                                                    <span class="text-xs font-bold text-gray-600">{{ substr($match->awayTeam->name, 0, 1) }}</span>
                                                </div>
                                            @endif
                                            <span class="mx-2 text-sm font-medium">{{ $match->awayTeam->name }}</span>
                                            @if($match->status === 'completed')
                                                <span class="text-gray-400">{{ $match->away_score ?? '-' }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm text-gray-500">{{ $match->match_date->format('H:i') }}</div>
                                        <div class="text-xs text-gray-400">{{ $match->venue->name ?? 'TBD' }}</div>
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $match->status === 'completed' ? 'bg-green-100 text-green-800' : ($match->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : ($match->status === 'scheduled' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800')) }}">
                                            {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-lg font-medium text-gray-900">No Recent Matches</h3>
                        <p class="mt-1 text-gray-600">Your teams haven't played any matches yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
