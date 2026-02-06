@extends('layouts.admin')

@section('title','Coach Dashboard')

@section('content')
<div class="max-w-10xl mx-auto">
    <!-- Success/Error Messages -->
    @if(session('success'))
        <x-ui.alert type="success" class="mb-6">{{ session('success') }}</x-ui.alert>
    @endif

    @if(session('error'))
        <x-ui.alert type="error" class="mb-6">{{ session('error') }}</x-ui.alert>
    @endif

    <!-- Page Header -->
    <x-ui.page-header 
        title="Coach Dashboard" 
        subtitle="Manage your teams, players, and matches"
    />

    <!-- Dashboard Stats -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <x-ui.stat-card 
            title="Total Teams" 
            :value="$teams->count()" 
            icon="fas fa-users" 
            icon-color="indigo"
        />
        
        <x-ui.stat-card 
            title="Total Players" 
            :value="$teams->sum(function($team) { return $team->players->count(); })" 
            icon="fas fa-user-friends" 
            icon-color="green"
        />
        
        <x-ui.stat-card 
            title="Upcoming Matches" 
            :value="$upcomingMatches->count()" 
            icon="fas fa-calendar-alt" 
            icon-color="yellow"
        />
    </div>

    <!-- Teams Overview -->
    @if($teams->count() > 0)
        <x-ui.card title="My Teams" icon="fas fa-users" class="mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($teams as $team)
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-5 hover:border-indigo-300 hover:shadow-md transition-all">
                        <div class="flex items-center mb-4">
                            @if($team->logo)
                                <img src="{{ asset('storage/' . $team->logo) }}" alt="{{ $team->name }}" class="w-12 h-12 rounded-full object-cover mr-3">
                            @else
                                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="fas fa-users text-indigo-600"></i>
                                </div>
                            @endif
                            <div class="flex-1">
                                <h4 class="text-lg font-semibold text-gray-900">{{ $team->name }}</h4>
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-trophy text-xs mr-1"></i>{{ $team->tournament->name ?? 'No Tournament' }}
                                </p>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg p-3 mb-4">
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div>
                                    <p class="text-gray-600 text-xs mb-1">Players</p>
                                    <p class="font-semibold text-gray-900">{{ $team->players->count() }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-600 text-xs mb-1">Coach</p>
                                    <p class="font-semibold text-gray-900 text-xs">{{ $team->coach_name ?? 'Not assigned' }}</p>
                                </div>
                            </div>
                        </div>
                        <x-ui.button 
                            href="{{ route('admin.teams.show', $team->id) }}" 
                            variant="primary" 
                            size="sm"
                            icon="fas fa-eye"
                            class="w-full"
                        >
                            View Team
                        </x-ui.button>
                    </div>
                @endforeach
            </div>
        </x-ui.card>
    @else
        <x-ui.card class="mb-8">
            <div class="text-center py-12">
                <i class="fas fa-users text-5xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Teams Assigned</h3>
                <p class="text-sm text-gray-600">You haven't been assigned to any teams yet. Contact an administrator to get started.</p>
            </div>
        </x-ui.card>
    @endif

    <!-- Match Schedule -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Upcoming Matches -->
        <x-ui.card title="Upcoming Matches" icon="fas fa-calendar-alt">
            <x-slot name="actions">
                <x-ui.badge variant="info">{{ $upcomingMatches->count() }} matches</x-ui.badge>
            </x-slot>
            <div class="max-h-96 overflow-y-auto">
                @if($upcomingMatches->count() > 0)
                    <div class="space-y-3">
                        @foreach($upcomingMatches as $match)
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 hover:border-indigo-300 transition-colors">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3 flex-1">
                                        <div class="text-sm font-medium text-gray-500 w-12">
                                            {{ $match->match_date->format('M d') }}
                                        </div>
                                        <div class="flex items-center flex-1 min-w-0">
                                            @if($match->homeTeam->logo)
                                                <img src="{{ asset('storage/' . $match->homeTeam->logo) }}" alt="{{ $match->homeTeam->name }}" class="w-6 h-6 rounded-full object-cover flex-shrink-0">
                                            @else
                                                <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center flex-shrink-0">
                                                    <span class="text-xs font-bold text-gray-600">{{ substr($match->homeTeam->name, 0, 1) }}</span>
                                                </div>
                                            @endif
                                            <span class="mx-2 text-sm font-medium text-gray-900 truncate">{{ $match->homeTeam->name }}</span>
                                            <span class="text-gray-400 mx-1">vs</span>
                                            @if($match->awayTeam->logo)
                                                <img src="{{ asset('storage/' . $match->awayTeam->logo) }}" alt="{{ $match->awayTeam->name }}" class="w-6 h-6 rounded-full object-cover flex-shrink-0">
                                            @else
                                                <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center flex-shrink-0">
                                                    <span class="text-xs font-bold text-gray-600">{{ substr($match->awayTeam->name, 0, 1) }}</span>
                                                </div>
                                            @endif
                                            <span class="mx-2 text-sm font-medium text-gray-900 truncate">{{ $match->awayTeam->name }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right ml-4">
                                        <div class="text-sm text-gray-500">{{ $match->match_date->format('H:i') }}</div>
                                        <div class="text-xs text-gray-400">{{ $match->venue->name ?? 'TBD' }}</div>
                                        <x-ui.badge :variant="$match->status === 'scheduled' ? 'info' : ($match->status === 'in_progress' ? 'warning' : ($match->status === 'completed' ? 'success' : 'error'))" size="sm" class="mt-1">
                                            {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                                        </x-ui.badge>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-calendar-times text-4xl text-gray-300 mb-3"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-1">No Upcoming Matches</h3>
                        <p class="text-sm text-gray-600">Your teams don't have any scheduled matches.</p>
                    </div>
                @endif
            </div>
        </x-ui.card>

        <!-- Recent Matches -->
        <x-ui.card title="Recent Matches" icon="fas fa-history">
            <x-slot name="actions">
                <x-ui.badge variant="default">{{ $recentMatches->count() }} matches</x-ui.badge>
            </x-slot>
            <div class="max-h-96 overflow-y-auto">
                @if($recentMatches->count() > 0)
                    <div class="space-y-3">
                        @foreach($recentMatches as $match)
                            <div class="border border-gray-200 rounded-lg p-4 hover:border-indigo-300 transition-colors {{ $match->status === 'completed' ? 'bg-green-50' : 'bg-white' }}">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3 flex-1">
                                        <div class="text-sm font-medium text-gray-500 w-12">
                                            {{ $match->match_date->format('M d') }}
                                        </div>
                                        <div class="flex items-center flex-1 min-w-0">
                                            @if($match->homeTeam->logo)
                                                <img src="{{ asset('storage/' . $match->homeTeam->logo) }}" alt="{{ $match->homeTeam->name }}" class="w-6 h-6 rounded-full object-cover flex-shrink-0">
                                            @else
                                                <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center flex-shrink-0">
                                                    <span class="text-xs font-bold text-gray-600">{{ substr($match->homeTeam->name, 0, 1) }}</span>
                                                </div>
                                            @endif
                                            <span class="mx-2 text-sm font-medium text-gray-900 truncate">{{ $match->homeTeam->name }}</span>
                                            @if($match->status === 'completed')
                                                <span class="text-indigo-600 font-semibold mx-1">{{ $match->home_score ?? '-' }}</span>
                                            @endif
                                            <span class="text-gray-400 mx-1">vs</span>
                                            @if($match->awayTeam->logo)
                                                <img src="{{ asset('storage/' . $match->awayTeam->logo) }}" alt="{{ $match->awayTeam->name }}" class="w-6 h-6 rounded-full object-cover flex-shrink-0">
                                            @else
                                                <div class="w-6 h-6 bg-gray-300 rounded-full flex items-center justify-center flex-shrink-0">
                                                    <span class="text-xs font-bold text-gray-600">{{ substr($match->awayTeam->name, 0, 1) }}</span>
                                                </div>
                                            @endif
                                            <span class="mx-2 text-sm font-medium text-gray-900 truncate">{{ $match->awayTeam->name }}</span>
                                            @if($match->status === 'completed')
                                                <span class="text-indigo-600 font-semibold mx-1">{{ $match->away_score ?? '-' }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right ml-4">
                                        <div class="text-sm text-gray-500">{{ $match->match_date->format('H:i') }}</div>
                                        <div class="text-xs text-gray-400">{{ $match->venue->name ?? 'TBD' }}</div>
                                        <x-ui.badge :variant="$match->status === 'completed' ? 'success' : ($match->status === 'in_progress' ? 'warning' : ($match->status === 'scheduled' ? 'info' : 'error'))" size="sm" class="mt-1">
                                            {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                                        </x-ui.badge>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8">
                        <i class="fas fa-history text-4xl text-gray-300 mb-3"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-1">No Recent Matches</h3>
                        <p class="text-sm text-gray-600">Your teams haven't played any matches yet.</p>
                    </div>
                @endif
            </div>
        </x-ui.card>
    </div>
</div>
@endsection
