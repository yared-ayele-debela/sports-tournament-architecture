@extends('public.layouts.app')

@section('title', 'Sports Tournament Management System')

@section('content')
<!-- Hero Section -->
<section class="bg-gradient-to-r from-primary to-blue-600 text-white py-20">
    <div class="container mx-auto px-4">
        <div class="text-center">
            <h1 class="text-5xl font-bold mb-6">Welcome to Tournament Hub</h1>
            <p class="text-xl text-blue-100 mb-8 max-w-2xl mx-auto">
                Your comprehensive platform for sports tournament management, team tracking, and match results.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('tournaments.index') }}" class="bg-white text-primary px-8 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                    <i class="fas fa-trophy mr-2"></i>Browse Tournaments
                </a>
                <a href="{{ route('teams.index') }}" class="bg-accent text-white px-8 py-3 rounded-lg font-semibold hover:bg-yellow-600 transition">
                    <i class="fas fa-users mr-2"></i>View Teams
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-12">Platform Statistics</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="text-center">
                <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-trophy text-white text-2xl"></i>
                </div>
                <div class="text-3xl font-bold text-primary mb-2">{{ \App\Models\Tournament::count() }}</div>
                <div class="text-gray-600">Tournaments</div>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-success rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-users text-white text-2xl"></i>
                </div>
                <div class="text-3xl font-bold text-success mb-2">{{ \App\Models\Team::count() }}</div>
                <div class="text-gray-600">Teams</div>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-accent rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-futbol text-white text-2xl"></i>
                </div>
                <div class="text-3xl font-bold text-accent mb-2">{{ \App\Models\MatchModel::count() }}</div>
                <div class="text-gray-600">Matches</div>
            </div>
            <div class="text-center">
                <div class="w-16 h-16 bg-secondary rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user text-white text-2xl"></i>
                </div>
                <div class="text-3xl font-bold text-secondary mb-2">{{ \App\Models\Player::count() }}</div>
                <div class="text-gray-600">Players</div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Tournaments -->
<section class="py-16 bg-gray-50">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-12">Featured Tournaments</h2>
        {{-- <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"> --}}
            @php
                $featuredTournaments = \App\Models\Tournament::with(['sport', 'teams'])
                    ->orderBy('start_date', 'desc')
                    ->take(3)
                    ->get();
            @endphp
             @if($featuredTournaments->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    @foreach($featuredTournaments as $tournament)
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                            <div class="relative">
                                {{-- <img src="https://picsum.photos/seed/{{ str_slug($tournament->name) }}/400/250.jpg" alt="{{ $tournament->name }}" class="w-full h-48 object-cover"> --}}
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
                                <p class="text-sm text-gray-600 mb-3">{{ $tournament->description ?? 'Exciting tournament competition featuring the best teams.' }}</p>
                                <div class="flex items-center justify-between text-sm text-gray-500 mb-3">
                                    <span><i class="fas fa-map-marker-alt mr-1"></i> {{ $tournament->location ?? 'TBD' }}</span>
                                    <span><i class="fas fa-users mr-1"></i> {{ $tournament->teams->count() }} Teams</span>
                                </div>
                                <div class="grid grid-cols-3 gap-2 mb-3">
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
                @if(method_exists($featuredTournaments, 'links'))
                    <div class="mt-12">
                        {{ $featuredTournaments->links() }}
                    </div>
                @endif
            @else
                <div class="text-center py-16">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No tournaments found</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        @if(request()->hasAny(['search', 'sport', 'status']))
                            Try adjusting your filters or 
                            <a href="{{ route('tournaments.index') }}" class="text-primary hover:text-primary-800 font-medium">
                                clear all filters
                            </a>
                        @else
                            No tournaments have been created yet.
                        @endif
                    </p>
                </div>
            @endif
        {{-- </div> --}}
        <div class="text-center mt-8">
            <a href="{{ route('tournaments.index') }}" class="inline-flex items-center text-primary hover:text-primary-800 font-semibold">
                View All Tournaments
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- Recent Matches -->
<section class="py-16 bg-white">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-12">Recent Matches</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            @php
                $recentMatches = \App\Models\MatchModel::with(['homeTeam', 'awayTeam', 'tournament'])
                    ->orderBy('match_date', 'desc')
                    ->take(6)
                    ->get();
            @endphp
            @foreach($recentMatches as $match)
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
        <div class="text-center mt-8">
            <a href="{{ route('matches.index') }}" class="inline-flex items-center text-primary hover:text-primary-800 font-semibold">
                View All Matches
                <i class="fas fa-arrow-right ml-2"></i>
            </a>
        </div>
    </div>
</section>
@endsection
