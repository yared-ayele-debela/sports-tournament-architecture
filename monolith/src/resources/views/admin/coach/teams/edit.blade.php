@extends('layouts.admin')


@section('title', 'Edit Team: ' . $team->name)

@section('content')
<div class="p-6">
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            <div class="flex">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 0 8 8 0 006 0zm-3.707-9.293a1 1 0 00-1.414 1.414L9 10.586 7.707a1 1 0 00-1.414 0l-2 2a1 1 0 001.414 1.414l2 2a1 1 0 001.414 0z" clip-rule="evenodd" />
                </svg>
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <div class="flex">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 0 8 8 0 006 0zm-3.707-9.293a1 1 0 00-1.414 1.414L9 10.586 7.707a1 1 0 00-1.414 0l-2 2a1 1 0 001.414 1.414l2 2a1 1 0 001.414 0z" clip-rule="evenodd" />
                </svg>
                {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- Edit Form -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Edit Team: {{ $team->name }}</h3>
        </div>
        <form action="{{ route('coach.teams.update', $team->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6 p-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Team Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Team Name</label>
                    <input type="text" 
                           id="name" 
                           name="name" 
                           value="{{ old('name', $team->name) }}" 
                           required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Coach Name -->
                <div>
                    <label for="coach_name" class="block text-sm font-medium text-gray-700">Coach Name</label>
                    <input type="text" 
                           id="coach_name" 
                           name="coach_name" 
                           value="{{ old('coach_name', $team->coach_name) }}" 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    @error('coach_name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Team Logo -->
            <div>
                <label for="logo" class="block text-sm font-medium text-gray-700">Team Logo</label>
                <div class="mt-1 flex items-center space-x-4">
                    <div class="flex-1">
                        <input type="file" 
                               id="logo" 
                               name="logo" 
                               accept="image/*"
                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        @error('logo')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">Allowed formats: JPEG, PNG, JPG, GIF, SVG. Max size: 2MB.</p>
                    </div>
                    @if($team->logo)
                        <div class="flex-shrink-0">
                            <img src="{{ asset('storage/' . $team->logo) }}" alt="Current logo" class="h-20 w-20 rounded-lg object-cover">
                            <p class="mt-2 text-sm text-gray-500">Current logo</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 px-6 py-4 bg-gray-50">
                <a href="{{ route('coach.teams.show', $team->id) }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Update Team
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
