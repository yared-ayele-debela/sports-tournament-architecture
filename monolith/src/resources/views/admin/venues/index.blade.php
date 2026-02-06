@extends('layouts.admin')

@section('title', 'Venues')

@section('content')
<div class="max-w-10xl mx-auto">
    <!-- Success Message -->
    @if(session('success'))
        <x-ui.alert type="success" class="mb-6">{{ session('success') }}</x-ui.alert>
    @endif

    <!-- Page Header -->
    <x-ui.page-header title="Venues" subtitle="Manage tournament venues and locations">
        @if(auth()->user()->hasPermission('manage_venues'))
            <x-slot name="actions">
                <x-ui.button href="{{ route('admin.venues.create') }}" variant="primary" icon="fas fa-plus">Add Venue</x-ui.button>
            </x-slot>
        @endif
    </x-ui.page-header>

    <!-- Venues Table -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Capacity</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($venues as $venue)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-map-marker-alt text-indigo-600"></i>
                                    </div>
                                    <div class="text-sm font-medium text-gray-900">{{ $venue->name }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <i class="fas fa-location-dot text-xs mr-1"></i>{{ $venue->location }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <i class="fas fa-users text-xs mr-1"></i>{{ $venue->capacity ? number_format($venue->capacity) : '—' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <i class="fas fa-calendar text-xs mr-1"></i>{{ $venue->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    @if(auth()->user()->hasPermission('manage_venues'))
                                        <a href="{{ route('admin.venues.show', $venue->id) }}" class="text-indigo-600 hover:text-indigo-900 transition-colors" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.venues.edit', $venue->id) }}" class="text-blue-600 hover:text-blue-900 transition-colors" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.venues.destroy', $venue->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this venue?')" class="inline">
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
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <i class="fas fa-map-marker-alt text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-lg font-medium">No venues found</p>
                                    <p class="text-sm mt-1">Get started by adding your first venue.</p>
                                    @if(auth()->user()->hasPermission('manage_venues'))
                                        <div class="mt-4">
                                            <x-ui.button href="{{ route('admin.venues.create') }}" variant="primary" icon="fas fa-plus">Add Venue</x-ui.button>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($venues->hasPages())
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $venues->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
