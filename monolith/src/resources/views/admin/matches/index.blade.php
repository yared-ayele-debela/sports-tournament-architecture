@extends('layouts.admin')

@section('title', 'Matches')

@section('content')
<div class="max-w-10xl mx-auto">
    <!-- Success Message -->
    @if(session('success'))
        <x-ui.alert type="success" class="mb-6">{{ session('success') }}</x-ui.alert>
    @endif

    <!-- Page Header -->
    <x-ui.page-header title="Matches" subtitle="Manage tournament matches and schedules">
        @if(auth()->user()->hasPermission('manage_matches'))
            <x-slot name="actions">
                <x-ui.button href="{{ route('admin.matches.create') }}" variant="primary" icon="fas fa-plus">Create Match</x-ui.button>
            </x-slot>
        @endif
    </x-ui.page-header>

    <!-- Matches Table -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Home Team</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Away Team</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tournament</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Venue</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Referee</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Date & Time</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Score</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Report</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($matches as $match)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if($match->homeTeam->logo)
                                        <img src="{{ asset('storage/' . $match->homeTeam->logo) }}" alt="{{ $match->homeTeam->name }}" class="h-8 w-8 rounded-full object-cover mr-2">
                                    @else
                                        <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center mr-2">
                                            <i class="fas fa-users text-indigo-600 text-xs"></i>
                                        </div>
                                    @endif
                                    <span class="text-sm font-medium text-gray-900">{{ $match->homeTeam->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if($match->awayTeam->logo)
                                        <img src="{{ asset('storage/' . $match->awayTeam->logo) }}" alt="{{ $match->awayTeam->name }}" class="h-8 w-8 rounded-full object-cover mr-2">
                                    @else
                                        <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center mr-2">
                                            <i class="fas fa-users text-indigo-600 text-xs"></i>
                                        </div>
                                    @endif
                                    <span class="text-sm font-medium text-gray-900">{{ $match->awayTeam->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <i class="fas fa-trophy text-xs mr-1"></i>{{ $match->tournament->name ?? '—' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <i class="fas fa-map-marker-alt text-xs mr-1"></i>{{ $match->venue->name ?? '—' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <i class="fas fa-user-shield text-xs mr-1"></i>{{ $match->referee->name ?? '—' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <i class="fas fa-calendar-alt text-xs mr-1"></i>{{ $match->match_date->format('M d, Y H:i') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-ui.badge :variant="str_replace('_', '-', $match->status) === 'completed' ? 'success' : (str_replace('_', '-', $match->status) === 'in-progress' ? 'warning' : (str_replace('_', '-', $match->status) === 'scheduled' ? 'info' : 'error'))">
                                    {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                                </x-ui.badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    @if($match->home_score !== null && $match->away_score !== null)
                                        {{ $match->home_score }} - {{ $match->away_score }}
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($match->matchReport)
                                    <x-ui.badge variant="success" class="inline-flex items-center">
                                        <i class="fas fa-file-alt mr-1 text-xs"></i>
                                        Available
                                    </x-ui.badge>
                                @else
                                    <span class="text-xs text-gray-400">
                                        <i class="fas fa-file-alt mr-1"></i>No report
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    @if(auth()->user()->hasPermission('manage_matches'))
                                        <a href="{{ route('admin.matches.show', $match->id) }}" class="text-indigo-600 hover:text-indigo-900 transition-colors" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.matches.edit', $match->id) }}" class="text-blue-600 hover:text-blue-900 transition-colors" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.matches.destroy', $match->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this match?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 transition-colors" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <i class="fas fa-calendar-times text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-lg font-medium">No matches found</p>
                                    <p class="text-sm mt-1">Get started by creating your first match.</p>
                                    @if(auth()->user()->hasPermission('manage_matches'))
                                        <div class="mt-4">
                                            <x-ui.button href="{{ route('admin.matches.create') }}" variant="primary" icon="fas fa-plus">Create Match</x-ui.button>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($matches->hasPages())
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $matches->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
