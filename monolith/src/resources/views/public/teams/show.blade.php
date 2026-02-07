@extends('public.layouts.app')

@section('title', $team->name . ' - Team Profile')

@section('content')
<!-- Team Header -->
@include('public.partials.page-header', [
    'title' => $team->name,
    'subtitle' => 'Team Profile and Performance Statistics'
])

<!-- Team Overview -->
<section class="py-6">
    <div class="container mx-auto px-4">
        <div class="bg-white rounded-lg shadow-md p-8">
            <div class="flex flex-col md:flex-row items-center gap-8">
                <div class="w-24 h-24 bg-primary rounded-full flex items-center justify-center">
                    <span class="text-white font-bold text-3xl">{{ substr($team->name, 0, 1) }}</span>
                </div>

                <div class="text-center md:text-left flex-1">
                    <div class="mb-2">
                        <span class="inline-block px-3 py-1 bg-primary bg-opacity-10 rounded-full text-sm font-semibold text-primary">
                            {{ $team->tournament->sport->name ?? 'Sport' }}
                        </span>
                    </div>
                    <h1 class="text-3xl font-bold mb-2">{{ $team->name }}</h1>
                    <div class="text-gray-600 space-y-1">
                        <div><i class="fas fa-trophy mr-2"></i>{{ $team->tournament->name ?? 'No Tournament' }}</div>
                        @if($team->coach_name)
                        <div><i class="fas fa-user mr-2"></i>Coach: {{ $team->coach_name }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Statistics -->
<section class="py-6 bg-gray-50">
    <div class="container mx-auto px-4">
        <h2 class="text-2xl font-bold text-center mb-8">Performance Statistics</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="text-3xl font-bold text-primary mb-2">{{ $stats['total_matches'] ?? 0 }}</div>
                <div class="text-gray-600">Total Matches</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="text-3xl font-bold text-success mb-2">{{ $stats['wins'] ?? 0 }}</div>
                <div class="text-gray-600">Wins</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="text-3xl font-bold text-primary mb-2">{{ $stats['draws'] ?? 0 }}</div>
                <div class="text-gray-600">Draws</div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <div class="text-3xl font-bold text-danger mb-2">{{ $stats['losses'] ?? 0 }}</div>
                <div class="text-gray-600">Losses</div>
            </div>
        </div>
    </div>
</section>

<!-- Players -->
<section class="py-6">
    <div class="container mx-auto px-4">
        <h2 class="text-2xl font-bold text-center mb-8">Team Players</h2>
        @if($team->players->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($team->players as $player)
                    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-all duration-300">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center mr-4">
                                <span class="text-primary font-bold">{{ substr($player->name, 0, 1) }}</span>
                            </div>
                            <div>
                                <h3 class="font-semibold">{{ $player->name }}</h3>
                                <div class="text-sm text-gray-500">#{{ $player->jersey_number }} • {{ $player->position ?? 'Player' }}</div>
                            </div>
                        </div>
                        <div class="text-sm text-gray-600">
                            <div><i class="fas fa-hashtag mr-1"></i>Jersey #{{ $player->jersey_number }}</div>
                            <div><i class="fas fa-futbol mr-1"></i>{{ $player->position ?? 'Player' }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            @include('public.partials.empty-state', [
                'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z',
                'title' => 'No players found',
                'message' => 'No players have been added to this team yet.'
            ])
        @endif
    </div>
</section>

<!-- Recent Matches -->
<section class="py-12 bg-gray-50">
    <div class="container mx-auto px-4">
        <h2 class="text-2xl font-bold text-center mb-8">Recent Matches</h2>
        @if(isset($recentMatches) && $recentMatches->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                @foreach($recentMatches as $match)
                    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition-all duration-300">
                        <div class="flex items-center justify-between mb-4">
                            <div class="text-sm text-gray-500">
                                <i class="fas fa-calendar mr-1"></i>{{ $match->match_date->format('M j, H:i') }}
                            </div>
                            <div class="px-2 py-1 text-xs font-semibold rounded-full
                                {{ $match->status === 'completed' ? 'bg-success text-white' :
                                   ($match->status === 'in_progress' ? 'bg-accent text-white' :
                                   'bg-gray-200 text-gray-700') }}">
                                {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="text-center flex-1">
                                <div class="font-semibold">{{ $match->homeTeam->name }}</div>
                            </div>
                            <div class="px-4">
                                <div class="text-2xl font-bold text-center">
                                    {{ $match->home_score ?? '-' }} : {{ $match->away_score ?? '-' }}
                                </div>
                            </div>
                            <div class="text-center flex-1">
                                <div class="font-semibold">{{ $match->awayTeam->name }}</div>
                            </div>
                        </div>
                        <div class="text-center mt-4 space-y-2">
                            @if($match->status === 'in_progress')
                                <a href="{{ route('matches.live', $match) }}" class="block w-full text-center bg-red-600 text-white py-2 rounded-lg hover:bg-red-700 transition font-semibold">
                                    <i class="fas fa-video mr-2"></i>Watch Live
                                </a>
                            @endif
                            <a href="{{ route('matches.show', $match->id) }}" class="text-primary hover:text-primary-800 text-sm font-semibold">
                                View Match Details
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            @include('public.partials.empty-state', [
                'icon' => 'M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
                'title' => 'No matches found',
                'message' => 'No matches have been played yet.'
            ])
        @endif
    </div>
</section>
@endsection
