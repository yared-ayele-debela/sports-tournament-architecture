@extends('layouts.admin')

@section('title', $tournamentSetting->tournament->name . ' Settings')

@section('content')
<div class="max-w-8xl mx-auto">
    <!-- Page Header -->
    <x-ui.page-header
        title="{{ $tournamentSetting->tournament->name }} Settings"
        subtitle="Tournament match settings and daily schedule"
    >
        <x-slot name="actions">
            <x-ui.button href="{{ route('admin.tournament-settings.index') }}" variant="secondary" icon="fas fa-arrow-left">Back to Settings</x-ui.button>
            <x-ui.button href="{{ route('admin.tournament-settings.edit', $tournamentSetting->id) }}" variant="primary" icon="fas fa-edit">Edit Settings</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Match Settings -->
        <x-ui.card title="Match Settings" icon="fas fa-clock">
            <dl class="space-y-4">
                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                    <dt class="text-sm font-medium text-gray-500 flex items-center">
                        <i class="fas fa-stopwatch text-indigo-600 mr-2"></i>Match Duration
                    </dt>
                    <dd class="text-sm text-gray-900 font-semibold">{{ $tournamentSetting->match_duration }} minutes</dd>
                </div>
                <div class="flex justify-between items-center py-3">
                    <dt class="text-sm font-medium text-gray-500 flex items-center">
                        <i class="fas fa-pause-circle text-indigo-600 mr-2"></i>Rest Time After Win
                    </dt>
                    <dd class="text-sm text-gray-900 font-semibold">{{ $tournamentSetting->win_rest_time }} minutes</dd>
                </div>
            </dl>
        </x-ui.card>

        <!-- Daily Schedule -->
        <x-ui.card title="Daily Schedule" icon="fas fa-calendar-alt">
            <dl class="space-y-4">
                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                    <dt class="text-sm font-medium text-gray-500 flex items-center">
                        <i class="fas fa-sun text-yellow-600 mr-2"></i>Start Time
                    </dt>
                    <dd class="text-sm text-gray-900 font-semibold">{{ $tournamentSetting->daily_start_time->format('H:i') }}</dd>
                </div>
                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                    <dt class="text-sm font-medium text-gray-500 flex items-center">
                        <i class="fas fa-moon text-blue-600 mr-2"></i>End Time
                    </dt>
                    <dd class="text-sm text-gray-900 font-semibold">{{ $tournamentSetting->daily_end_time->format('H:i') }}</dd>
                </div>
                <div class="flex justify-between items-center py-3">
                    <dt class="text-sm font-medium text-gray-500 flex items-center">
                        <i class="fas fa-hourglass-half text-indigo-600 mr-2"></i>Daily Duration
                    </dt>
                    <dd class="text-sm text-gray-900 font-semibold">
                        {{ \Carbon\Carbon::parse($tournamentSetting->daily_start_time)->diffInMinutes(\Carbon\Carbon::parse($tournamentSetting->daily_end_time)) }} minutes
                    </dd>
                </div>
            </dl>
        </x-ui.card>

        <!-- Settings Information -->
        <x-ui.card title="Settings Information" icon="fas fa-info-circle">
            <dl class="space-y-4">
                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                    <dt class="text-sm font-medium text-gray-500 flex items-center">
                        <i class="fas fa-trophy text-indigo-600 mr-2"></i>Tournament
                    </dt>
                    <dd class="text-sm text-gray-900 font-semibold">{{ $tournamentSetting->tournament->name }}</dd>
                </div>
                <div class="flex justify-between items-center py-3 border-b border-gray-200">
                    <dt class="text-sm font-medium text-gray-500 flex items-center">
                        <i class="fas fa-calendar-plus text-green-600 mr-2"></i>Created
                    </dt>
                    <dd class="text-sm text-gray-900 font-semibold">{{ $tournamentSetting->created_at->format('F j, Y H:i') }}</dd>
                </div>
                <div class="flex justify-between items-center py-3">
                    <dt class="text-sm font-medium text-gray-500 flex items-center">
                        <i class="fas fa-edit text-blue-600 mr-2"></i>Last Updated
                    </dt>
                    <dd class="text-sm text-gray-900 font-semibold">{{ $tournamentSetting->updated_at->format('F j, Y H:i') }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <!-- Quick Actions -->
        <x-ui.card title="Quick Actions" icon="fas fa-bolt">
            <div class="space-y-3">
                <x-ui.button href="{{ route('admin.tournament-settings.edit', $tournamentSetting->id) }}" variant="primary" icon="fas fa-edit" class="w-full">
                    Edit Settings
                </x-ui.button>

                <form action="{{ route('admin.tournament-settings.destroy', $tournamentSetting->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete these tournament settings? This action cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <x-ui.button type="submit" variant="danger" icon="fas fa-trash" class="w-full">
                        Delete Settings
                    </x-ui.button>
                </form>
            </div>
        </x-ui.card>
    </div>
</div>
@endsection
