@extends('layouts.admin')
@section('title', 'Match Events - ' . $match->homeTeam->name . ' vs ' . $match->awayTeam->name)
@section('content')
<div class="max-w-8xl mx-auto">
    <!-- Header -->
     <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">Match Events</h1>
                    <p class="text-blue-100">{{ $match->homeTeam->name }} vs {{ $match->awayTeam->name }}</p>
                </div>
                <div class="text-sm">
                    <span class="font-medium">{{ Auth::user()->name }}</span>
                    <span class="mx-2">•</span>
                    <span>Referee</span>
                </div>
            </div>
        </div>
    </div>

     <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <!-- Match Summary -->
        <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
            <div class="grid md:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900">{{ $match->home_score ?? 0 }}</div>
                    <div class="text-sm text-gray-600">{{ $match->homeTeam->name }}</div>
                </div>
                <div class="text-center">
                    <div class="text-4xl font-bold text-gray-500">:</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900">{{ $match->away_score ?? 0 }}</div>
                    <div class="text-sm text-gray-600">{{ $match->awayTeam->name }}</div>
                </div>
                <div class="text-center">
                    <div class="text-lg font-semibold text-blue-600">{{ $match->current_minute ?? 0 }}</div>
                    <div class="text-sm text-gray-600">Current Minute</div>
                </div>
            </div>
        </div>

        <!-- Events List -->
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold text-gray-800">All Events</h2>
                    <a href="{{ route('admin.referee.matches.show', $match) }}" 
                       class="text-blue-600 hover:text-blue-800 font-medium">
                        ← Back to Match
                    </a>
                </div>
            </div>
            
            @if($events->count() > 0)
                <div class="divide-y divide-gray-200">
                    @foreach($events as $event)
                        <div class="p-6 hover:bg-gray-50">
                            <div class="flex items-start space-x-4">
                                <!-- Event Icon & Type -->
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 rounded-full flex items-center justify-center 
                                        @if($event->event_type === 'goal')
                                            bg-green-100 text-green-600
                                        @elseif($event->event_type === 'yellow_card')
                                            bg-yellow-100 text-yellow-600
                                        @elseif($event->event_type === 'red_card')
                                            bg-red-100 text-red-600
                                        @elseif($event->event_type === 'substitution')
                                            bg-blue-100 text-blue-600
                                        @else
                                            bg-gray-100 text-gray-600
                                        @endif">
                                        @if($event->event_type === 'goal')
                                            ⚽
                                        @elseif($event->event_type === 'yellow_card')
                                            🟨
                                        @elseif($event->event_type === 'red_card')
                                            🟥
                                        @elseif($event->event_type === 'substitution')
                                            🔄
                                        @else
                                            📝
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Event Details -->
                                <div class="flex-1">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center space-x-3">
                                            <span class="text-lg font-semibold text-gray-900">
                                                {{ $event->minute }}'
                                            </span>
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full 
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
                                                    Goal
                                                @elseif($event->event_type === 'yellow_card')
                                                    Yellow Card
                                                @elseif($event->event_type === 'red_card')
                                                    Red Card
                                                @elseif($event->event_type === 'substitution')
                                                    Substitution
                                                @else
                                                    Event
                                                @endif
                                            </span>
                                            <span class="text-sm text-gray-600">
                                                {{ $event->team->name }}
                                            </span>
                                            @if($event->player)
                                                <span class="text-sm text-gray-600">
                                                    ({{ $event->player->name }})
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <!-- Actions -->
                                        <div class="flex space-x-2">
                                            <form action="{{ route('admin.referee.events.update', [$match, $event]) }}" method="POST">
                                                @csrf
                                                @method('PUT')
                                                <button type="submit" 
                                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                    Edit
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.referee.events.destroy', [$match, $event]) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="text-red-600 hover:text-red-800 text-sm font-medium"
                                                        onclick="return confirm('Are you sure you want to delete this event?')">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    @if($event->description)
                                        <div class="text-sm text-gray-600 mt-2 bg-gray-50 p-3 rounded">
                                            <strong>Description:</strong> {{ $event->description }}
                                        </div>
                                    @endif
                                    
                                    <!-- Event Time -->
                                    <div class="text-xs text-gray-500 mt-2">
                                        Recorded: {{ $event->created_at->format('M j, Y H:i:s') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m0 0l-3-3m3 3v-4m0 0l-3-3m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No events recorded</h3>
                    <p class="mt-2 text-sm text-gray-600">No events have been recorded for this match yet.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
