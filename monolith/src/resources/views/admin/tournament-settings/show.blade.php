@extends('layouts.admin')

@section('title', $tournamentSetting->tournament->name . ' Settings')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $tournamentSetting->tournament->name }} Settings</h1>
                <p class="text-gray-600 mt-1">Tournament match settings and daily schedule</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.tournament-settings.index') }}" class="inline-flex items-center px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Settings
                </a>
                
                <a href="{{ route('admin.tournament-settings.edit', $tournamentSetting->id) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-4h-4v4m0 0l4-4m-4 0v6m0 0l4 4" />
                    </svg>
                    Edit Settings
                </a>
            </div>
        </div>
    </div>

    <!-- Settings Details -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Main Settings -->
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Match Settings</h3>
                        <dl class="space-y-3">
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Match Duration</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ $tournamentSetting->match_duration }} minutes</dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Rest Time After Win</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ $tournamentSetting->win_rest_time }} minutes</dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Daily Schedule</h3>
                        <dl class="space-y-3">
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Start Time</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ $tournamentSetting->daily_start_time->format('H:i') }}</dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">End Time</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ $tournamentSetting->daily_end_time->format('H:i') }}</dd>
                            </div>
                            <div class="flex justify-between py-2">
                                <dt class="text-sm font-medium text-gray-500">Daily Duration</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    {{ \Carbon\Carbon::parse($tournamentSetting->daily_start_time)->diffInMinutes(\Carbon\Carbon::parse($tournamentSetting->daily_end_time)) }} minutes
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Statistics & Actions -->
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Settings Information</h3>
                        <dl class="space-y-3">
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Tournament</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ $tournamentSetting->tournament->name }}</dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Created</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ $tournamentSetting->created_at->format('F j, Y H:i') }}</dd>
                            </div>
                            <div class="flex justify-between py-2">
                                <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ $tournamentSetting->updated_at->format('F j, Y H:i') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <a href="{{ route('admin.tournament-settings.edit', $tournamentSetting->id) }}" class="block w-full text-center px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-4h-4v4m0 0l4-4m-4 0v6m0 0l4 4" />
                                </svg>
                                Edit Settings
                            </a>
                            
                            <form action="{{ route('admin.tournament-settings.destroy', $tournamentSetting->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete these tournament settings? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="block w-full text-center px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m0 0L4-4m-4 0v6m0 0L4 4" />
                                    </svg>
                                    Delete Settings
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
