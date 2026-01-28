@extends('layouts.admin')

@section('title', 'Referee Dashboard')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Referee Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Referee Dashboard</h1>
                    <p class="text-blue-100">Manage matches and record events</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="font-medium">{{ Auth::user()->name }}</span>
                    <span class="mx-2">•</span>
                    <span>Referee</span>
                </div>
        </div>
    </div>

    <!-- Active Matches -->
     <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Active Matches</h2>
            
            @if($matches->count() > 0)
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($matches as $match)
                        <div class="bg-white rounded-lg border border-gray-200 p-6 hover:border-gray-300 transition-colors">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="font-semibold text-lg">{{ $match->homeTeam->name }} vs {{ $match->awayTeam->name }}</h3>
                                    <p class="text-sm text-gray-600">{{ $match->tournament->name }}</p>
                                    <p class="text-sm text-gray-600">{{ $match->match_date->format('M j, Y H:i') }}</p>
                                </div>
                                <span class="px-3 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">
                                    LIVE
                                </span>
                            </div>
                            
                            <div class="text-sm text-gray-600 mb-4">
                                <div class="flex justify-between">
                                    <span>Current Minute: <strong>{{ $match->current_minute ?? 0 }}</strong></span>
                                    <span>Score: <strong>{{ $match->home_score ?? 0 }} - {{ $match->away_score ?? 0 }}</strong></span>
                                </div>
                            </div>
                            
                            <div class="flex space-x-2">
                                <a href="{{ route('admin.referee.matches.show', $match) }}" 
                                   class="flex-1 text-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                    Manage Match
                                </a>
                                <a href="{{ route('admin.referee.events.index', $match) }}" 
                                   class="flex-1 text-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                                    View Events
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12 bg-white rounded-lg border border-gray-200">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m0 0l-3-3m3 3v-4m0 0l-3-3m3 3v-4m0 0l-3-3m-3 3h-6m-9-3v6m0 0v6h6m-9-3v6m0 0v6h6"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No active matches</h3>
                    <p class="mt-2 text-sm text-gray-600">You don't have any matches currently in progress.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
