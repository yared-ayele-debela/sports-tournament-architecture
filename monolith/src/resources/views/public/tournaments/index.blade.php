@extends('public.layouts.app')

@section('title', 'Tournaments - Sports Tournament Management')

@section('content')
<!-- Page Header -->
@include('public.partials.page-header', [
    'title' => 'Tournaments',
    'subtitle' => 'Browse and join exciting sports tournaments'
])

<!-- Filters -->
@include('public.partials.filters', [
    'action' => route('tournaments.index'),
    'sport' => true,
    'sports' => $sports ?? [],
    'status' => true,
    'search' => true,
    'search_placeholder' => 'Search tournaments...'
])

<!-- Tournaments Grid -->
<section class="py-12">
    <div class="container mx-auto px-4">
        @if($tournaments->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($tournaments as $tournament)
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="relative">
                            <span class="absolute top-3 right-3 px-2 py-1 text-xs font-semibold rounded-full
                                {{ $tournament->start_date <= now() && $tournament->end_date >= now() ? 'bg-success text-white' :
                                   ($tournament->start_date > now() ? 'bg-accent text-white' :
                                   'bg-secondary text-white') }}">
                                {{ $tournament->start_date > now() ? 'Upcoming' :
                                   ($tournament->end_date < now() ? 'Completed' : 'Live') }}
                            </span>
                        </div>
                        <div class="p-5">
                            <div class="flex items-center justify-between mb-2">
                                <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-primary text-white">{{ $tournament->sport->name ?? 'Sport' }}</span>
                                <span class="text-xs text-gray-500"><i class="fas fa-calendar mr-1"></i> {{ $tournament->start_date->format('M Y') }}</span>
                            </div>
                            <h3 class="text-lg font-bold mb-2">{{ $tournament->name }}</h3>
                            <div class="flex items-center justify-between text-sm text-gray-500 mb-3">
                                @if($tournament->location)
                                <span><i class="fas fa-map-marker-alt mr-1"></i> {{ $tournament->location }}</span>
                                @endif
                                <span><i class="fas fa-users mr-1"></i> {{ $tournament->teams->count() }} Teams</span>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <a href="{{ route('tournaments.show', $tournament->id) }}" class="text-center px-2 py-1 bg-primary text-white text-xs rounded hover:bg-blue-600 transition">
                                    Overview
                                </a>
                                <a href="{{ route('tournaments.matches', $tournament->id) }}" class="text-center px-2 py-1 bg-success text-white text-xs rounded hover:bg-green-600 transition">
                                    Matches
                                </a>
                                <a href="{{ route('tournaments.standings', $tournament->id) }}" class="text-center px-2 py-1 bg-accent text-white text-xs rounded hover:bg-yellow-600 transition">
                                    Standings
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            @include('public.partials.pagination', ['data' => $tournaments])
        @else
            @include('public.partials.empty-state', [
                'icon' => 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
                'title' => 'No tournaments found',
                'message' => request()->hasAny(['search', 'sport', 'status'])
                    ? 'Try adjusting your filters or clear all filters'
                    : 'No tournaments have been created yet.',
                'action_text' => request()->hasAny(['search', 'sport', 'status']) ? 'clear all filters' : null,
                'action_url' => request()->hasAny(['search', 'sport', 'status']) ? route('tournaments.index') : null
            ])
        @endif
    </div>
</section>
@endsection
