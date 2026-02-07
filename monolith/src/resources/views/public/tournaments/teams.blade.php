@extends('public.layouts.app')

@section('title', $tournament->name . ' - Teams')

@section('content')
<!-- Tournament Header -->
@include('public.partials.page-header', [
    'title' => $tournament->name,
    'subtitle' => 'Participating Teams'
])
<!-- Tournament Navigation -->
@include('public.partials.tournament-tabs', ['tournament' => $tournament])
<!-- Filters -->
@include('public.partials.filters', [
    'action' => route('tournaments.teams', $tournament)
])
<!-- Teams Grid -->
<section class="py-12">
    <div class="container mx-auto px-4">
        @if($teams->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @foreach($teams as $team)
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                        <div class="relative">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                            <div class="absolute bottom-4 left-4 right-4">
                                <div class="w-12 h-12 bg-primary rounded-full flex items-center justify-center mb-2">
                                    <span class="text-white font-bold">{{ substr($team->name, 0, 1) }}</span>
                                </div>
                                <h3 class="text-lg font-bold text-white">{{ $team->name }}</h3>
                            </div>
                        </div>

                        <div class="p-5">
                            <div class="flex items-center justify-between mb-3">
                                <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-primary text-white">
                                    {{ $tournament->sport->name ?? 'Sport' }}
                                </span>
                                @if($team->coach_name)
                                <span class="text-xs text-gray-500">
                                    <i class="fas fa-user mr-1"></i>{{ $team->coach_name }}
                                </span>
                                @endif
                            </div>

                            <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                                <span><i class="fas fa-users mr-1"></i>{{ $team->players_count ?? $team->players->count() }} Players</span>
                            </div>

                            <div class="grid grid-cols-2 gap-2">
                                <a href="{{ route('teams.show', $team->id) }}" class="text-center px-2 py-2 bg-primary text-white text-xs rounded hover:bg-blue-600 transition">
                                    Profile
                                </a>
                                <a href="{{ route('tournaments.matches', $tournament) }}?team={{ $team->id }}" class="text-center px-2 py-2 bg-success text-white text-xs rounded hover:bg-green-600 transition">
                                    Matches
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <!-- Pagination -->
            @include('public.partials.pagination', ['data' => $teams])
        @else
            @include('public.partials.empty-state', [
                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                'title' => 'No teams found',
                'message' => 'No teams have registered for this tournament yet.'
            ])
        @endif
    </div>
</section>
@endsection
