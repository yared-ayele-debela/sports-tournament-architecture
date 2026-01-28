@extends('layouts.admin')

@section('title', 'Edit Tournament Settings')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Tournament Settings</h1>
                <p class="text-gray-600 mt-1">Update match duration and daily schedule</p>
            </div>
            <a href="{{ route('admin.tournament-settings.index') }}" class="inline-flex items-center px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Settings
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white shadow rounded-lg">
        <form action="{{ route('admin.tournament-settings.update', $tournamentSetting->id) }}" method="POST" class="p-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Tournament Field -->
                <div>
                    <label for="tournament_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Tournament <span class="text-red-500">*</span>
                    </label>
                    <select id="tournament_id" 
                            name="tournament_id" 
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('tournament_id') ? 'border-red-500' : '' @enderror"
                            required>
                        <option value="">Select a tournament</option>
                        @foreach($tournaments as $tournament)
                            <option value="{{ $tournament->id }}" {{ old('tournament_id', $tournamentSetting->tournament_id) == $tournament->id ? 'selected' : '' }}>
                                {{ $tournament->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('tournament_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Match Duration Field -->
                <div>
                    <label for="match_duration" class="block text-sm font-medium text-gray-700 mb-2">
                        Match Duration (minutes) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           id="match_duration" 
                           name="match_duration" 
                           value="{{ old('match_duration', $tournamentSetting->match_duration) }}"
                           min="1"
                           max="480"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('match_duration') ? 'border-red-500' : '' @enderror"
                           placeholder="Enter match duration in minutes"
                           required>
                    @error('match_duration')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Duration of each match in minutes (1-480)</p>
                </div>

                <!-- Win Rest Time Field -->
                <div>
                    <label for="win_rest_time" class="block text-sm font-medium text-gray-700 mb-2">
                        Rest Time After Win (minutes) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           id="win_rest_time" 
                           name="win_rest_time" 
                           value="{{ old('win_rest_time', $tournamentSetting->win_rest_time) }}"
                           min="0"
                           max="60"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('win_rest_time') ? 'border-red-500' : '' @enderror"
                           placeholder="Enter rest time in minutes"
                           required>
                    @error('win_rest_time')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Rest time between matches after a win (0-60 minutes)</p>
                </div>

                <!-- Daily Start Time Field -->
                <div>
                    <label for="daily_start_time" class="block text-sm font-medium text-gray-700 mb-2">
                        Daily Start Time <span class="text-red-500">*</span>
                    </label>
                    <input type="time" 
                           id="daily_start_time" 
                           name="daily_start_time" 
                           value="{{ old('daily_start_time', $tournamentSetting->daily_start_time->format('H:i')) }}"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('daily_start_time') ? 'border-red-500' : '' @enderror"
                           required>
                    @error('daily_start_time')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Daily match schedule start time</p>
                </div>

                <!-- Daily End Time Field -->
                <div>
                    <label for="daily_end_time" class="block text-sm font-medium text-gray-700 mb-2">
                        Daily End Time <span class="text-red-500">*</span>
                    </label>
                    <input type="time" 
                           id="daily_end_time" 
                           name="daily_end_time" 
                           value="{{ old('daily_end_time', $tournamentSetting->daily_end_time->format('H:i')) }}"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('daily_end_time') ? 'border-red-500' : '' @enderror"
                           required>
                    @error('daily_end_time')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Daily match schedule end time</p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="mt-8 flex items-center justify-end space-x-4">
                <a href="{{ route('admin.tournament-settings.index') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Update Settings
                </button>
            </div>
        </form>
    </div>

    <!-- Settings Info -->
    <div class="mt-6 bg-gray-50 rounded-lg p-4">
        <h3 class="text-sm font-medium text-gray-900 mb-2">Settings Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500">Tournament:</span>
                <span class="text-gray-900 font-medium">{{ $tournamentSetting->tournament->name ?? 'Not assigned' }}</span>
            </div>
            <div>
                <span class="text-gray-500">Created:</span>
                <span class="text-gray-900 font-medium">{{ $tournamentSetting->created_at->format('M d, Y H:i') }}</span>
            </div>
            <div>
                <span class="text-gray-500">Last Updated:</span>
                <span class="text-gray-900 font-medium">{{ $tournamentSetting->updated_at->format('M d, Y H:i') }}</span>
            </div>
            <div>
                <span class="text-gray-500">Match Duration:</span>
                <span class="text-gray-900 font-medium">{{ $tournamentSetting->match_duration }} minutes</span>
            </div>
            <div>
                <span class="text-gray-500">Rest Time:</span>
                <span class="text-gray-900 font-medium">{{ $tournamentSetting->win_rest_time }} minutes</span>
            </div>
            <div>
                <span class="text-gray-500">Daily Schedule:</span>
                <span class="text-gray-900 font-medium">
                    {{ $tournamentSetting->daily_start_time->format('H:i') }} - {{ $tournamentSetting->daily_end_time->format('H:i') }}
                </span>
            </div>
        </div>
    </div>
</div>
@endsection
