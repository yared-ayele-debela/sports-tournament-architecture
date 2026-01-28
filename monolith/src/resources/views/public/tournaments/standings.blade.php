@extends('public.layouts.app')

@section('title', $tournament->name . ' - Standings')

@section('content')
<!-- Tournament Header -->
@include('public.partials.page-header', [
    'title' => $tournament->name,
    'subtitle' => 'League Standings & Rankings'
])

<!-- Tournament Navigation -->
@include('public.partials.tournament-tabs', ['tournament' => $tournament])

<!-- Standings Section -->
<section class="py-12">
    <div class="container mx-auto px-4">
        @if($standings->count() > 0)
            <!-- Top 3 Teams -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                @php
                    $podiumTeams = $standings->take(3);
                    $positions = ['1st', '2nd', '3rd'];
                    $medals = ['🥇', '🥈', '🥉'];
                    $colors = ['bg-yellow-100 border-yellow-400', 'bg-gray-100 border-gray-400', 'bg-orange-100 border-orange-400'];
                @endphp
                
                @foreach($podiumTeams as $index => $standing)
                    <div class="bg-white rounded-lg shadow-md p-6 border-2 {{ $colors[$index] }} text-center">
                        <div class="text-4xl mb-2">{{ $medals[$index] }}</div>
                        <div class="text-2xl font-bold mb-2">{{ $positions[$index] }} Place</div>
                        <div class="w-16 h-16 bg-primary rounded-full flex items-center justify-center mx-auto mb-3">
                            <span class="text-white font-bold text-xl">{{ substr($standing->team->name, 0, 1) }}</span>
                        </div>
                        <h3 class="text-lg font-bold mb-2">{{ $standing->team->name }}</h3>
                        <div class="text-3xl font-bold text-primary mb-1">{{ $standing->points }}</div>
                        <div class="text-sm text-gray-600">Points</div>
                        <div class="mt-4 text-sm text-gray-600">
                            <span class="text-success font-semibold">{{ $standing->won }}W</span>
                            <span class="mx-2">-</span>
                            <span class="text-gray-600">{{ $standing->drawn }}D</span>
                            <span class="mx-2">-</span>
                            <span class="text-danger font-semibold">{{ $standing->lost }}L</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Full Standings Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="p-6 border-b">
                    <h2 class="text-2xl font-bold">Complete Standings</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pos</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Team</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">P</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">W</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">D</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">L</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">GF</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">GA</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">GD</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Pts</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($standings as $standing)
                                <tr class="hover:bg-gray-50 {{ $standing->position <= 3 ? 'bg-gradient-to-r from-transparent to-yellow-50' : '' }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($standing->position == 1)
                                                <span class="text-2xl mr-2">🥇</span>
                                            @elseif($standing->position == 2)
                                                <span class="text-2xl mr-2">🥈</span>
                                            @elseif($standing->position == 3)
                                                <span class="text-2xl mr-2">🥉</span>
                                            @endif
                                            <span class="text-sm font-medium {{ $standing->position <= 3 ? 'text-lg font-bold text-primary' : '' }}">
                                                {{ $standing->position }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center mr-3">
                                                <span class="text-white font-bold text-sm">{{ substr($standing->team->name, 0, 1) }}</span>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 {{ $standing->position <= 3 ? 'font-bold' : '' }}">
                                                    {{ $standing->team->name }}
                                                </div>
                                                <a href="{{ route('teams.show', $standing->team) }}" class="text-xs text-primary hover:text-primary-800">
                                                    View Profile
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">{{ $standing->played }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-medium text-success">{{ $standing->won }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">{{ $standing->drawn }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-medium text-danger">{{ $standing->lost }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">{{ $standing->goals_for }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center">{{ $standing->goals_against }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-medium">
                                        <span class="{{ $standing->goal_difference > 0 ? 'text-success' : ($standing->goal_difference < 0 ? 'text-danger' : 'text-gray-600') }}">
                                            {{ $standing->goal_difference > 0 ? '+' : '' }}{{ $standing->goal_difference }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold text-primary {{ $standing->position <= 3 ? 'text-xl' : '' }}">
                                        {{ $standing->points }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            @include('public.partials.empty-state', [
                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                'title' => 'No standings available',
                'message' => 'Standings will be available once matches are completed.'
            ])
        @endif
    </div>
</section>
@endsection
