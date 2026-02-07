@extends('layouts.admin')

@section('title', 'Referee Dashboard')

@section('content')
<div class="max-w-10xl mx-auto">
    <!-- Page Header -->
    <x-ui.page-header 
        title="Referee Dashboard" 
        subtitle="Manage matches and record events"
    />

    <!-- Active Matches -->
    <x-ui.card title="Active Matches" icon="fas fa-futbol">
        @if($matches->count() > 0)
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($matches as $match)
                    <div class="bg-gray-50 rounded-lg border border-gray-200 p-6 hover:border-indigo-300 hover:shadow-md transition-all">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex-1">
                                <h3 class="font-semibold text-lg text-gray-900 mb-1">{{ $match->homeTeam->name }} vs {{ $match->awayTeam->name }}</h3>
                                <p class="text-sm text-gray-600 mb-1">
                                    <i class="fas fa-trophy text-xs mr-1"></i>{{ $match->tournament->name }}
                                </p>
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-calendar-alt text-xs mr-1"></i>{{ $match->match_date->format('M j, Y H:i') }}
                                </p>
                            </div>
                            <x-ui.badge variant="warning">LIVE</x-ui.badge>
                        </div>
                        
                        <div class="bg-white rounded-lg p-4 mb-4">
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div>
                                    <p class="text-gray-600 text-xs mb-1">Current Minute</p>
                                    <p class="font-semibold text-gray-900">{{ $match->current_minute ?? 0 }}'</p>
                                </div>
                                <div>
                                    <p class="text-gray-600 text-xs mb-1">Score</p>
                                    <p class="font-semibold text-gray-900">{{ $match->home_score ?? 0 }} - {{ $match->away_score ?? 0 }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex space-x-2">
                            <x-ui.button 
                                href="{{ route('admin.referee.matches.show', $match) }}" 
                                variant="primary" 
                                size="sm"
                                icon="fas fa-cog"
                                class="flex-1"
                            >
                                Manage Match
                            </x-ui.button>
                            <x-ui.button 
                                href="{{ route('admin.referee.events.index', $match) }}" 
                                variant="success" 
                                size="sm"
                                icon="fas fa-list"
                                class="flex-1"
                            >
                                View Events
                            </x-ui.button>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-futbol text-5xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No active matches</h3>
                <p class="text-sm text-gray-600">You don't have any matches currently in progress.</p>
            </div>
        @endif
    </x-ui.card>
</div>
@endsection
