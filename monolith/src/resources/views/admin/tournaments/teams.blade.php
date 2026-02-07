@extends('layouts.admin')

@section('title', $tournament->name . ' - Teams')

@section('content')
<div class="max-w-10xl mx-auto">
    <!-- Page Header -->
    <x-ui.page-header :title="$tournament->name" subtitle="Tournament Teams">
        <x-slot name="actions">
            <a href="{{ route('admin.tournaments.index') }}" class="inline-flex items-center px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i>Back to Tournaments
            </a>
        </x-slot>
    </x-ui.page-header>

    <!-- Teams Table -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">Participating Teams</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $teams->count() }} team(s) registered</p>
                </div>
            </div>
        </div>

        @if($teams->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Team Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Coach</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Players</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($teams as $team)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @if($team->logo)
                                            <img src="{{ asset('storage/' . $team->logo) }}" alt="{{ $team->name }}" class="h-10 w-10 rounded-full object-cover mr-3">
                                        @else
                                            <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center mr-3">
                                                <span class="text-indigo-600 font-bold text-sm">{{ substr($team->name, 0, 1) }}</span>
                                            </div>
                                        @endif
                                        <div class="text-sm font-medium text-gray-900">{{ $team->name }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">
                                        @if($team->coach_name)
                                            <i class="fas fa-user mr-1"></i>{{ $team->coach_name }}
                                        @elseif($team->coaches->count() > 0)
                                            <i class="fas fa-user mr-1"></i>{{ $team->coaches->first()->name }}
                                        @else
                                            <span class="text-gray-400">No coach assigned</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="text-sm text-gray-900 font-medium">{{ $team->players_count ?? 0 }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('admin.teams.show', $team->id) }}" class="text-indigo-600 hover:text-indigo-900 transition-colors" title="View Team">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-12 text-center">
                <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                <p class="text-lg font-medium text-gray-500">No teams registered</p>
                <p class="text-sm text-gray-400 mt-1">No teams have been registered for this tournament yet.</p>
            </div>
        @endif
    </div>
</div>
@endsection
