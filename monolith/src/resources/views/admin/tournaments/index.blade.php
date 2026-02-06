@extends('layouts.admin')

@section('title', 'Tournaments')

@section('content')
<div class="max-w-10xl mx-auto">
    <!-- Success Message -->
    @if(session('success'))
        <x-ui.alert type="success" class="mb-6">{{ session('success') }}</x-ui.alert>
    @endif

    <!-- Page Header -->
    <x-ui.page-header title="Tournaments Management" subtitle="Manage all tournaments in the system">
        @if(auth()->user()->hasPermission('manage_tournaments'))
            <x-slot name="actions">
                <x-ui.button href="{{ route('admin.tournaments.create') }}" variant="primary" icon="fas fa-plus">Create Tournament</x-ui.button>
            </x-slot>
        @endif
    </x-ui.page-header>

    <!-- Tournaments Table -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Sport</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Start Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">End Date</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Teams</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($tournaments as $tournament)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-trophy text-indigo-600"></i>
                                    </div>
                                    <div class="text-sm font-medium text-gray-900">{{ $tournament->name }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <i class="fas fa-futbol text-xs mr-1"></i>{{ $tournament->sport->name ?? '—' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <i class="fas fa-calendar-alt text-xs mr-1"></i>{{ $tournament->start_date->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <i class="fas fa-calendar-check text-xs mr-1"></i>{{ $tournament->end_date->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-ui.badge :variant="$tournament->status === 'active' ? 'success' : ($tournament->status === 'completed' ? 'info' : ($tournament->status === 'cancelled' ? 'error' : 'default'))">
                                    {{ ucfirst($tournament->status) }}
                                </x-ui.badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <i class="fas fa-users text-xs mr-1"></i>{{ $tournament->teams_count ?? 0 }} / {{ $tournament->max_teams ?? '∞' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    @if(auth()->user()->hasPermission('manage_tournaments'))
                                        <a href="{{ route('admin.tournaments.show', $tournament->id) }}" class="text-indigo-600 hover:text-indigo-900 transition-colors" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.tournaments.edit', $tournament->id) }}" class="text-blue-600 hover:text-blue-900 transition-colors" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.tournaments.destroy', $tournament->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this tournament?')" class="inline">
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
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <i class="fas fa-trophy text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-lg font-medium">No tournaments found</p>
                                    <p class="text-sm mt-1">Get started by creating your first tournament.</p>
                                    @if(auth()->user()->hasPermission('manage_tournaments'))
                                        <div class="mt-4">
                                            <x-ui.button href="{{ route('admin.tournaments.create') }}" variant="primary" icon="fas fa-plus">Create Tournament</x-ui.button>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($tournaments->hasPages())
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $tournaments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
