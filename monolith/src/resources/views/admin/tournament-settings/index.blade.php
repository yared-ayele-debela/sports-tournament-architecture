@extends('layouts.admin')

@section('title', 'Tournament Settings')

@section('content')
<div class="max-w-10xl mx-auto">
    <!-- Success Message -->
    @if(session('success'))
        <x-ui.alert type="success" class="mb-6">{{ session('success') }}</x-ui.alert>
    @endif

    <!-- Page Header -->
    <x-ui.page-header title="Tournament Settings" subtitle="Manage tournament match settings and daily schedules">
        @if(auth()->user()->hasPermission('manage_tournaments'))
            <x-slot name="actions">
                <x-ui.button href="{{ route('admin.tournament-settings.create') }}" variant="primary" icon="fas fa-plus">Create Settings</x-ui.button>
            </x-slot>
        @endif
    </x-ui.page-header>

    <!-- Tournament Settings Table -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Tournament</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Match Duration</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Rest Time</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Daily Schedule</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($settings as $setting)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-trophy text-indigo-600"></i>
                                    </div>
                                    <div class="text-sm font-medium text-gray-900">{{ $setting->tournament->name ?? '—' }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <i class="fas fa-clock text-xs mr-1"></i>{{ $setting->match_duration }} minutes
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <i class="fas fa-pause-circle text-xs mr-1"></i>{{ $setting->win_rest_time }} minutes
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <i class="fas fa-calendar-alt text-xs mr-1"></i>
                                    {{ $setting->daily_start_time->format('H:i') }} - {{ $setting->daily_end_time->format('H:i') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <i class="fas fa-calendar text-xs mr-1"></i>{{ $setting->created_at->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    @if(auth()->user()->hasPermission('manage_tournaments'))
                                        <a href="{{ route('admin.tournament-settings.show', $setting->id) }}" class="text-indigo-600 hover:text-indigo-900 transition-colors" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.tournament-settings.edit', $setting->id) }}" class="text-blue-600 hover:text-blue-900 transition-colors" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.tournament-settings.destroy', $setting->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete these tournament settings?')" class="inline">
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
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <i class="fas fa-cog text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-lg font-medium">No tournament settings found</p>
                                    <p class="text-sm mt-1">Get started by creating your first tournament settings.</p>
                                    @if(auth()->user()->hasPermission('manage_tournaments'))
                                        <div class="mt-4">
                                            <x-ui.button href="{{ route('admin.tournament-settings.create') }}" variant="primary" icon="fas fa-plus">Create Settings</x-ui.button>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
