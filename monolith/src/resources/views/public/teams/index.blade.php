@extends('public.layouts.app')

@section('title', 'Teams - Sports Tournament Management')

@section('content')
<!-- Page Header -->
@include('public.partials.page-header', [
    'title' => 'All Teams',
    'subtitle' => 'Browse teams and their performance statistics across tournaments'
])

<!-- Filters -->
@include('public.partials.filters', [
    'action' => route('teams.index'),
    'tournament' => true,
    'tournaments' => $tournaments ?? [],
    'search' => true,
    'search_placeholder' => 'Search teams...'
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
                                <div class="text-sm text-white opacity-90">{{ $team->tournament->name ?? 'No Tournament' }}</div>
                            </div>
                        </div>
                        
                        <div class="p-5">
                            <div class="flex items-center justify-between mb-3">
                                <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full bg-primary text-white">
                                    {{ $team->sport->name ?? 'Sport' }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    <i class="fas fa-user mr-1"></i>{{ $team->coach_name ?? 'No Coach' }}
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-3 gap-2 mb-4 text-center">
                                <div>
                                    <div class="text-lg font-bold text-success">
                                        {{ \App\Models\MatchModel::where(function($query) use ($team) {
                                            $query->where('home_team_id', $team->id)->where('home_score', '>', 'away_score');
                                        })->orWhere(function($query) use ($team) {
                                            $query->where('away_team_id', $team->id)->where('away_score', '>', 'home_score');
                                        })->count() }}
                                    </div>
                                    <div class="text-xs text-gray-600">Wins</div>
                                </div>
                                <div>
                                    <div class="text-lg font-bold text-primary">
                                        {{ \App\Models\MatchModel::where(function($query) use ($team) {
                                            $query->where('home_team_id', $team->id)->where('home_score', '=', 'away_score');
                                        })->orWhere(function($query) use ($team) {
                                            $query->where('away_team_id', $team->id)->where('away_score', '=', 'home_score');
                                        })->count() }}
                                    </div>
                                    <div class="text-xs text-gray-600">Draws</div>
                                </div>
                                <div>
                                    <div class="text-lg font-bold text-danger">
                                        {{ \App\Models\MatchModel::where(function($query) use ($team) {
                                            $query->where('home_team_id', $team->id)->where('home_score', '<', 'away_score');
                                        })->orWhere(function($query) use ($team) {
                                            $query->where('away_team_id', $team->id)->where('away_score', '<', 'home_score');
                                        })->count() }}
                                    </div>
                                    <div class="text-xs text-gray-600">Losses</div>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                                <span><i class="fas fa-users mr-1"></i>{{ $team->players_count ?? $team->players->count() }} Players</span>
                                <span><i class="fas fa-chart-line mr-1"></i>{{ $team->homeMatches->count() + $team->awayMatches->count() }} Matches</span>
                            </div>
                            
                            <a href="{{ route('teams.show', $team->id) }}" class="block w-full text-center bg-primary text-white py-2 rounded-lg hover:bg-blue-600 transition">
                                View Profile
                            </a>
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
                'message' => request()->hasAny(['search', 'tournament']) 
                    ? 'Try adjusting your filters or clear all filters' 
                    : 'No teams have been created yet.',
                'action_text' => request()->hasAny(['search', 'tournament']) ? 'clear all filters' : null,
                'action_url' => request()->hasAny(['search', 'tournament']) ? route('teams.index') : null
            ])
        @endif
    </div>
</section>
@endsection