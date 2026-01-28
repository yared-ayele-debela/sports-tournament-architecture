@extends('public.layouts.app')

@section('title', 'Matches - Sports Tournament Management')

@section('content')
<!-- Page Header -->
@include('public.partials.page-header', [
    'title' => 'Matches',
    'subtitle' => 'View upcoming and completed match results'
])

<!-- Filters -->
@include('public.partials.filters', [
    'action' => route('matches.index'),
    'tournament' => true,
    'tournaments' => $tournaments ?? [],
    'status' => true,
    'search' => true,
    'search_placeholder' => 'Search matches...'
])

<!-- Matches Grid -->
<section class="py-12">
    <div class="container mx-auto px-4">
        @if($matches->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($matches as $match)
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <!-- Match Header -->
                        <div class="p-4">
                            <div class="flex justify-between items-center text-gray-700">
                                <div class="text-sm opacity-90">{{ $match->tournament->name }}</div>
                                <div class="px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $match->status === 'completed' ? 'bg-success text-white' : 
                                       ($match->status === 'in_progress' ? 'bg-accent text-white' : 
                                       'bg-gray-200 text-gray-700') }}">
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
                            
                            <a href="{{ route('matches.show', $match) }}" class="block w-full text-center bg-primary text-white py-2 rounded-lg hover:bg-blue-600 transition">
                                View Match Details
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <!-- Pagination -->
            @include('public.partials.pagination', ['data' => $matches])
        @else
            @include('public.partials.empty-state', [
                'icon' => 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
                'title' => 'No matches found',
                'message' => request()->hasAny(['search', 'tournament', 'status']) 
                    ? 'Try adjusting your filters or clear all filters' 
                    : 'No matches have been scheduled yet.',
                'action_text' => request()->hasAny(['search', 'tournament', 'status']) ? 'clear all filters' : null,
                'action_url' => request()->hasAny(['search', 'tournament', 'status']) ? route('matches.index') : null
            ])
        @endif
    </div>
</section>
@endsection
