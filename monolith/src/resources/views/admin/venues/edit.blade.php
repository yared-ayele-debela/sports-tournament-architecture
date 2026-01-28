@extends('layouts.admin')

@section('title', 'Edit Venue')

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit Venue</h1>
                <p class="text-gray-600 mt-1">Update venue information</p>
            </div>
            <a href="{{ route('admin.venues.index') }}" class="inline-flex items-center px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Venues
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white shadow rounded-lg">
        <form action="{{ route('admin.venues.update', $venue->id) }}" method="POST" class="p-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Name Field -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Venue Name <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name', $venue->name) }}"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('name') ? 'border-red-500' : '' @enderror"
                           placeholder="Enter venue name"
                           required>
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Location Field -->
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700 mb-2">
                        Location <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="location" 
                           name="location" 
                           value="{{ old('location', $venue->location) }}"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('location') ? 'border-red-500' : '' @enderror"
                           placeholder="Enter venue location"
                           required>
                    @error('location')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Capacity Field -->
                <div>
                    <label for="capacity" class="block text-sm font-medium text-gray-700 mb-2">
                        Capacity
                    </label>
                    <input type="number" 
                           id="capacity" 
                           name="capacity" 
                           value="{{ old('capacity', $venue->capacity) }}"
                           min="1"
                           max="999999"
                           class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('capacity') ? 'border-red-500' : '' @enderror"
                           placeholder="Enter venue capacity">
                    @error('capacity')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Maximum number of spectators (optional)</p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="mt-8 flex items-center justify-end space-x-4">
                <a href="{{ route('admin.venues.index') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Update Venue
                </button>
            </div>
        </form>
    </div>

    <!-- Venue Info -->
    <div class="mt-6 bg-gray-50 rounded-lg p-4">
        <h3 class="text-sm font-medium text-gray-900 mb-2">Venue Information</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500">Current Name:</span>
                <span class="text-gray-900 font-medium">{{ $venue->name }}</span>
            </div>
            <div>
                <span class="text-gray-500">Current Location:</span>
                <span class="text-gray-900 font-medium">{{ $venue->location }}</span>
            </div>
            <div>
                <span class="text-gray-500">Current Capacity:</span>
                <span class="text-gray-900 font-medium">{{ $venue->capacity ? number_format($venue->capacity) : 'Not set' }}</span>
            </div>
            <div>
                <span class="text-gray-500">Created:</span>
                <span class="text-gray-900 font-medium">{{ $venue->created_at->format('M d, Y H:i') }}</span>
            </div>
            <div>
                <span class="text-gray-500">Last Updated:</span>
                <span class="text-gray-900 font-medium">{{ $venue->updated_at->format('M d, Y H:i') }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
