@extends('layouts.admin')

@section('title', 'Sports')

@section('content')
<div class="max-w-10xl mx-auto">
    <!-- Success Message -->
    @if(session('success'))
        <x-ui.alert type="success" class="mb-6">{{ session('success') }}</x-ui.alert>
    @endif

    <!-- Page Header -->
    <x-ui.page-header title="Sports Management" subtitle="Manage all sports in the system">
        @if(auth()->user()->hasPermission('manage_sports'))
            <x-slot name="actions">
                <x-ui.button href="{{ route('admin.sports.create') }}" variant="primary" icon="fas fa-plus">Add Sport</x-ui.button>
            </x-slot>
        @endif
    </x-ui.page-header>

    <!-- Sports Table -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($sports as $sport)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-futbol text-indigo-600"></i>
                                    </div>
                                    <div class="text-sm font-medium text-gray-900">{{ $sport->name }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-500 max-w-xs truncate">{{ $sport->description ?? '—' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-ui.badge :variant="$sport->is_active ? 'success' : 'default'">
                                    {{ $sport->is_active ? 'Active' : 'Inactive' }}
                                </x-ui.badge>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <i class="fas fa-calendar text-xs mr-1"></i>{{ $sport->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    @if(auth()->user()->hasPermission('manage_sports'))
                                        <a href="{{ route('admin.sports.edit', $sport->id) }}" class="text-blue-600 hover:text-blue-900 transition-colors" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.sports.destroy', $sport->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this sport?')" class="inline">
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
                                    <i class="fas fa-futbol text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-lg font-medium">No sports found</p>
                                    <p class="text-sm mt-1">Get started by creating your first sport.</p>
                                    @if(auth()->user()->hasPermission('manage_sports'))
                                        <div class="mt-4">
                                            <x-ui.button href="{{ route('admin.sports.create') }}" variant="primary" icon="fas fa-plus">Create Sport</x-ui.button>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($sports->hasPages())
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $sports->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
