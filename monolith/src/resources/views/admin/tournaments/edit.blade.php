@extends('layouts.admin')

@section('title', 'Edit Tournament')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Tournament</h1>
                <p class="text-gray-600 mt-1">Update tournament information and settings</p>
            </div>
            <a href="{{ route('admin.tournaments.index') }}" class="inline-flex items-center px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Tournaments
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white shadow rounded-lg">
        <form action="{{ route('admin.tournaments.update', $tournament->id) }}" method="POST" class="p-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Name Field -->
                <div class="lg:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Tournament Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name', $tournament->name) }}"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('name') ? 'border-red-500' : '' @enderror"
                           placeholder="Enter tournament name"
                           required>
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Sport Field -->
                <div>
                    <label for="sport_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Sport <span class="text-red-500">*</span>
                    </label>
                    <select id="sport_id" 
                            name="sport_id" 
                            class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('sport_id') ? 'border-red-500' : '' @enderror"
                            required>
                        <option value="">Select a sport</option>
                        @foreach($sports as $sport)
                            <option value="{{ $sport->id }}" {{ old('sport_id', $tournament->sport_id) == $sport->id ? 'selected' : '' }}>
                                {{ $sport->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('sport_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description Field -->
                <div class="lg:col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Description
                    </label>
                    <textarea id="description" 
                              name="description" 
                              rows="4" 
                              class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('description') ? 'border-red-500' : '' @enderror"
                              placeholder="Enter tournament description (optional)">{{ old('description', $tournament->description) }}</textarea>
                    @error('description')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Optional: Provide additional details about this tournament</p>
                </div>

                <!-- Start Date Field -->
                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                        Start Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" 
                           id="start_date" 
                           name="start_date" 
                           value="{{ old('start_date', $tournament->start_date->format('Y-m-d')) }}"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('start_date') ? 'border-red-500' : '' @enderror"
                           required>
                    @error('start_date')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- End Date Field -->
                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                        End Date <span class="text-red-500">*</span>
                    </label>
                    <input type="date" 
                           id="end_date" 
                           name="end_date" 
                           value="{{ old('end_date', $tournament->end_date->format('Y-m-d')) }}"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('end_date') ? 'border-red-500' : '' @enderror"
                           required>
                    @error('end_date')
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
                        <option value="draft" {{ old('status', $tournament->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="active" {{ old('status', $tournament->status) == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ old('status', $tournament->status) == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ old('status', $tournament->status) == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    @error('status')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Max Teams Field -->
                <div>
                    <label for="max_teams" class="block text-sm font-medium text-gray-700 mb-2">
                        Maximum Teams
                    </label>
                    <input type="number" 
                           id="max_teams" 
                           name="max_teams" 
                           value="{{ old('max_teams', $tournament->max_teams) }}"
                           min="2"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('max_teams') ? 'border-red-500' : '' @enderror"
                           placeholder="Enter maximum number of teams">
                    @error('max_teams')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Optional: Leave empty for unlimited teams</p>
                </div>

                <!-- Registration Deadline Field -->
                <div>
                    <label for="registration_deadline" class="block text-sm font-medium text-gray-700 mb-2">
                        Registration Deadline
                    </label>
                    <input type="date" 
                           id="registration_deadline" 
                           name="registration_deadline" 
                           value="{{ old('registration_deadline', $tournament->registration_deadline?->format('Y-m-d')) }}"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('registration_deadline') ? 'border-red-500' : '' @enderror"
                           placeholder="Last date for team registration">
                    @error('registration_deadline')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Optional: Deadline for team registration</p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="mt-8 flex items-center justify-end space-x-4">
                <a href="{{ route('admin.tournaments.index') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Update Tournament
                </button>
            </div>
        </form>
    </div>

    <!-- Tournament Info -->
    <div class="mt-6 bg-gray-50 rounded-lg p-4">
        <h3 class="text-sm font-medium text-gray-900 mb-2">Tournament Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500">Created:</span>
                <span class="text-gray-900 font-medium">{{ $tournament->created_at->format('M d, Y H:i') }}</span>
            </div>
            <div>
                <span class="text-gray-500">Last Updated:</span>
                <span class="text-gray-900 font-medium">{{ $tournament->updated_at->format('M d, Y H:i') }}</span>
            </div>
            <div>
                <span class="text-gray-500">Sport:</span>
                <span class="text-gray-900 font-medium">{{ $tournament->sport->name ?? 'Not assigned' }}</span>
            </div>
            <div>
                <span class="text-gray-500">Status:</span>
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                    {{ $tournament->status === 'active' ? 'bg-green-100 text-green-800' : 
                       ($tournament->status === 'completed' ? 'bg-blue-100 text-blue-800' : 
                       ($tournament->status === 'cancelled' ? 'bg-red-100 text-red-800' : 
                       'bg-gray-100 text-gray-800')) }}">
                    {{ ucfirst($tournament->status) }}
                </span>
            </div>
            <div>
                <span class="text-gray-500">Duration:</span>
                <span class="text-gray-900 font-medium">
                    {{ $tournament->start_date->format('M d') }} - {{ $tournament->end_date->format('M d, Y') }}
                </span>
            </div>
            <div>
                <span class="text-gray-500">Teams:</span>
                <span class="text-gray-900 font-medium">
                    {{ $tournament->teams_count ?? 0 }} / {{ $tournament->max_teams ?? '∞' }}
                </span>
            </div>
        </div>
    </div>
</div>
@endsection
