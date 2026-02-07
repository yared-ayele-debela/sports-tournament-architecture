@extends('layouts.admin')

@section('title', $tournament->name . ' - Standings')

@section('content')
<div class="max-w-10xl mx-auto">
    <!-- Page Header -->
    <x-ui.page-header :title="$tournament->name" subtitle="Tournament Standings">
        <x-slot name="actions">
            <a href="{{ route('admin.tournaments.index') }}" class="inline-flex items-center px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Tournaments
            </a>
        </x-slot>
    </x-ui.page-header>

    <!-- Standings Table -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">League Standings</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $standings->count() }} team(s) in standings</p>
                </div>
            </div>
        </div>

        @if($standings->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pos</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Team</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">P</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">W</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">D</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">L</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">GF</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">GA</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">GD</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Pts</th>
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
                                        <span class="text-sm font-medium {{ $standing->position <= 3 ? 'text-lg font-bold text-indigo-600' : '' }}">
                                            {{ $standing->position }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                                            <span class="text-indigo-600 font-bold text-sm">{{ substr($standing->team->name, 0, 1) }}</span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 {{ $standing->position <= 3 ? 'font-bold' : '' }}">
                                                {{ $standing->team->name }}
                                            </div>
                                            <a href="{{ route('admin.teams.show', $standing->team->id) }}" class="text-xs text-indigo-600 hover:text-indigo-800">
                                                View Team
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">{{ $standing->played }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-medium text-green-600">{{ $standing->won }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">{{ $standing->drawn }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-medium text-red-600">{{ $standing->lost }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">{{ $standing->goals_for }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">{{ $standing->goals_against }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-medium">
                                    <span class="{{ $standing->goal_difference > 0 ? 'text-green-600' : ($standing->goal_difference < 0 ? 'text-red-600' : 'text-gray-600') }}">
                                        {{ $standing->goal_difference > 0 ? '+' : '' }}{{ $standing->goal_difference }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center font-bold text-indigo-600 {{ $standing->position <= 3 ? 'text-xl' : '' }}">
                                    {{ $standing->points }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-12 text-center">
                <i class="fas fa-trophy text-4xl text-gray-300 mb-3"></i>
                <p class="text-lg font-medium text-gray-500">No standings available</p>
                <p class="text-sm text-gray-400 mt-1">Standings will be available once matches are completed.</p>
            </div>
        @endif
    </div>
</div>
@endsection
