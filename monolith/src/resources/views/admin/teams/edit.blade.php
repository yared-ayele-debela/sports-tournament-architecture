@extends('layouts.admin')

@section('title', 'Edit Team')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Team</h1>
                <p class="text-gray-600 mt-1">Update team information</p>
            </div>
            <a href="{{ route('admin.teams.index') }}" class="inline-flex items-center px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Teams
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white shadow rounded-lg">
        <form action="{{ route('admin.teams.update', $team->id) }}" method="POST" enctype="multipart/form-data" class="p-6">
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
                            <option value="{{ $tournament->id }}" {{ old('tournament_id', $team->tournament_id) == $tournament->id ? 'selected' : '' }}>
                                {{ $tournament->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('tournament_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Team Name Field -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Team Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name', $team->name) }}"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('name') ? 'border-red-500' : '' @enderror"
                           placeholder="Enter team name"
                           required>
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Coach Selection Field -->
                <div>
                    <label for="coach_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Assign Coach
                    </label>
                    <select id="coach_id" 
                            name="coach_id" 
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('coach_id') ? 'border-red-500' : '' @enderror">
                        <option value="">No Coach Assigned</option>
                        @foreach($coaches as $coach)
                            <option value="{{ $coach->id }}" {{ old('coach_id', optional($team->coaches->first())->id) == $coach->id ? 'selected' : '' }}>
                                {{ $coach->name }} ({{ $coach->email }})
                            </option>
                        @endforeach
                    </select>
                    @error('coach_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Select a coach to manage this team. You can assign coaches later if needed.</p>
                </div>

                <!-- Logo Field -->
                <div>
                    <label for="logo" class="block text-sm font-medium text-gray-700 mb-2">
                        Team Logo
                    </label>
                    <input type="file" 
                           id="logo" 
                           name="logo" 
                           accept="image/*"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('logo') ? 'border-red-500' : '' @enderror">
                    @error('logo')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Optional: Upload new team logo (JPEG, PNG, GIF, SVG - Max 2MB)</p>
                    
                    @if($team->logo)
                        <div class="mt-3">
                            <p class="text-xs text-gray-500 mb-2">Current logo:</p>
                            <img src="{{ asset('storage/' . $team->logo) }}" alt="{{ $team->name }}" class="h-16 w-16 rounded-lg object-cover border border-gray-200">
                        </div>
                    @endif
                </div>
            </div>

            <!-- Form Actions -->
            <div class="mt-8 flex items-center justify-end space-x-4">
                <a href="{{ route('admin.teams.index') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Update Team
                </button>
            </div>
        </form>
    </div>

    <!-- Team Info -->
    <div class="mt-6 bg-gray-50 rounded-lg p-4">
        <h3 class="text-sm font-medium text-gray-900 mb-2">Team Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500">Current Name:</span>
                <span class="text-gray-900 font-medium">{{ $team->name }}</span>
            </div>
            <div>
                <span class="text-gray-500">Current Coach:</span>
                <span class="text-gray-900 font-medium">{{ $team->coach_name ?? 'Not set' }}</span>
            </div>
            <div>
                <span class="text-gray-500">Current Tournament:</span>
                <span class="text-gray-900 font-medium">{{ $team->tournament->name ?? 'Not assigned' }}</span>
            </div>
            <div>
                <span class="text-gray-500">Created:</span>
                <span class="text-gray-900 font-medium">{{ $team->created_at->format('M d, Y H:i') }}</span>
            </div>
            <div>
                <span class="text-gray-500">Last Updated:</span>
                <span class="text-gray-900 font-medium">{{ $team->updated_at->format('M d, Y H:i') }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
