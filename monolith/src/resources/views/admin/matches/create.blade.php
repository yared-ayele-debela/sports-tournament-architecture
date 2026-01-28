@extends('layouts.admin')

@section('title', 'Create Match')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Create Match</h1>
                <p class="text-gray-600 mt-1">Schedule a new tournament match</p>
            </div>
            <a href="{{ route('admin.matches.index') }}" class="inline-flex items-center px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Matches
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white shadow rounded-lg">
        <form action="{{ route('admin.matches.store') }}" method="POST" class="p-6">
            @csrf
            
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
                            <option value="{{ $tournament->id }}" {{ old('tournament_id') == $tournament->id ? 'selected' : '' }}>
                                {{ $tournament->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('tournament_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Venue Field -->
                <div>
                    <label for="venue_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Venue
                    </label>
                    <select id="venue_id" 
                            name="venue_id" 
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('venue_id') ? 'border-red-500' : '' @enderror">
                        <option value="">Select a venue</option>
                        @foreach($venues as $venue)
                            <option value="{{ $venue->id }}" {{ old('venue_id') == $venue->id ? 'selected' : '' }}>
                                {{ $venue->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('venue_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Optional: Select match venue</p>
                </div>

                <!-- Home Team Field -->
                <div>
                    <label for="home_team_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Home Team <span class="text-red-500">*</span>
                    </label>
                    <select id="home_team_id" 
                            name="home_team_id" 
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('home_team_id') ? 'border-red-500' : '' @enderror"
                            required>
                        <option value="">Select home team</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}" {{ old('home_team_id') == $team->id ? 'selected' : '' }}>
                                {{ $team->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('home_team_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Away Team Field -->
                <div>
                    <label for="away_team_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Away Team <span class="text-red-500">*</span>
                    </label>
                    <select id="away_team_id" 
                            name="away_team_id" 
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('away_team_id') ? 'border-red-500' : '' @enderror"
                            required>
                        <option value="">Select away team</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}" {{ old('away_team_id') == $team->id ? 'selected' : '' }}>
                                {{ $team->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('away_team_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Referee Field -->
                <div>
                    <label for="referee_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Referee
                    </label>
                    <select id="referee_id" 
                            name="referee_id" 
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('referee_id') ? 'border-red-500' : '' @enderror">
                        <option value="">Select a referee</option>
                        @foreach($referees as $referee)
                            <option value="{{ $referee->id }}" {{ old('referee_id') == $referee->id ? 'selected' : '' }}>
                                {{ $referee->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('referee_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Optional: Assign a referee to this match</p>
                </div>

                <!-- Match Date Field -->
                <div>
                    <label for="match_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Match Date & Time <span class="text-red-500">*</span>
                    </label>
                    <input type="datetime-local" 
                           id="match_date" 
                           name="match_date" 
                           value="{{ old('match_date') }}"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('match_date') ? 'border-red-500' : '' @enderror"
                           required>
                    @error('match_date')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Match must be scheduled for future date/time</p>
                </div>

                <!-- Round Number Field -->
                <div>
                    <label for="round_number" class="block text-sm font-medium text-gray-700 mb-2">
                        Round Number <span class="text-red-500">*</span>
                    </label>
                    <input type="number" 
                           id="round_number" 
                           name="round_number" 
                           value="{{ old('round_number') }}"
                           min="1"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('round_number') ? 'border-red-500' : '' @enderror"
                           placeholder="Enter round number"
                           required>
                    @error('round_number')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Status Field -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select id="status" 
                            name="status" 
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('status') ? 'border-red-500' : '' @enderror"
                            required>
                        <option value="scheduled" {{ old('status', 'scheduled') ? 'selected' : '' }}>Scheduled</option>
                        <option value="in_progress" {{ old('status', 'in_progress') ? 'selected' : '' }}>In Progress</option>
                        <option value="completed" {{ old('status', 'completed') ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ old('status', 'cancelled') ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    @error('status')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Home Score Field -->
                <div>
                    <label for="home_score" class="block text-sm font-medium text-gray-700 mb-2">
                        Home Score
                    </label>
                    <input type="number" 
                           id="home_score" 
                           name="home_score" 
                           value="{{ old('home_score') }}"
                           min="0"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('home_score') ? 'border-red-500' : '' @enderror"
                           placeholder="Enter home score">
                    @error('home_score')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Optional: Home team score</p>
                </div>

                <!-- Away Score Field -->
                <div>
                    <label for="away_score" class="block text-sm font-medium text-gray-700 mb-2">
                        Away Score
                    </label>
                    <input type="number" 
                           id="away_score" 
                           name="away_score" 
                           value="{{ old('away_score') }}"
                           min="0"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('away_score') ? 'border-red-500' : '' @enderror"
                           placeholder="Enter away score">
                    @error('away_score')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Optional: Away team score</p>
                </div>

                <!-- Current Minute Field -->
                <div>
                    <label for="current_minute" class="block text-sm font-medium text-gray-700 mb-2">
                        Current Minute
                    </label>
                    <input type="number" 
                           id="current_minute" 
                           name="current_minute" 
                           value="{{ old('current_minute') }}"
                           min="0"
                           max="120"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('current_minute') ? 'border-red-500' : '' @enderror"
                           placeholder="Enter current minute">
                    @error('current_minute')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Optional: Current match minute (0-120)</p>
                </div>
            </div>

            <!-- Team Validation Error -->
            @error('teams')
                <div class="mt-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                    <p class="text-sm">{{ $message }}</p>
                </div>
            @enderror

            <!-- Form Actions -->
            <div class="mt-8 flex items-center justify-end space-x-4">
                <a href="{{ route('admin.matches.index') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Create Match
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
