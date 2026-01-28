@extends('layouts.admin')

@section('title', 'Edit Player: ' . $player->full_name)

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

    <!-- Header -->
    <div class="mb-6 bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center space-x-4">
                @if($team->logo)
                    <img src="{{ asset('storage/' . $team->logo) }}" alt="{{ $team->name }}" class="h-10 w-10 rounded-full object-cover">
                @else
                    <div class="h-10 w-10 bg-gray-300 rounded-full flex items-center justify-center">
                        <svg class="h-6 w-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.356-1.857" />
                        </svg>
                    </div>
                @endif
                <div>
                    <h3 class="text-lg font-medium text-gray-900">Edit Player: {{ $player->full_name }}</h3>
                    <p class="text-sm text-gray-600">{{ $team->name }} - {{ $team->tournament->name ?? 'No Tournament' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Player Information</h3>
        </div>
        <form action="{{ route('admin.coach.players.update', [$team->id, $player->id]) }}" method="POST" class="space-y-6 p-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Player Name -->
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" 
                           id="full_name" 
                           name="full_name" 
                           value="{{ old('full_name', $player->full_name) }}" 
                           required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                           placeholder="Enter player's full name">
                    @error('full_name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Jersey Number -->
                <div>
                    <label for="jersey_number" class="block text-sm font-medium text-gray-700">Jersey Number</label>
                    <input type="number" 
                           id="jersey_number" 
                           name="jersey_number" 
                           value="{{ old('jersey_number', $player->jersey_number) }}" 
                           required
                           min="1"
                           max="99"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                           placeholder="Enter jersey number (1-99)">
                    @error('jersey_number')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">Must be unique within the team (1-99).</p>
                </div>
            </div>

            <!-- Position -->
            <div>
                <label for="position" class="block text-sm font-medium text-gray-700">Position</label>
                <select id="position" 
                        name="position" 
                        required
                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="">Select a position</option>
                    <option value="Goalkeeper" {{ old('position', $player->position) === 'Goalkeeper' ? 'selected' : '' }}>Goalkeeper</option>
                    <option value="Defender" {{ old('position', $player->position) === 'Defender' ? 'selected' : '' }}>Defender</option>
                    <option value="Midfielder" {{ old('position', $player->position) === 'Midfielder' ? 'selected' : '' }}>Midfielder</option>
                    <option value="Forward" {{ old('position', $player->position) === 'Forward' ? 'selected' : '' }}>Forward</option>
                </select>
                @error('position')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 px-6 py-4 bg-gray-50">
                <a href="{{ route('admin.coach.players.show', [$team->id, $player->id]) }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-4h-4m0 0l4 4m-4 0v6m0 0l4 4" />
                    </svg>
                    Update Player
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
