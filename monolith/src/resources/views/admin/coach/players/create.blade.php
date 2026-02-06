@extends('layouts.admin')

@section('title', 'Add Player - ' . $team->name)

@section('content')
<div class="p-6">
    
    <!-- Success/Error Messages -->
    @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            <div class="flex">
                <i class="fas fa-check-circle w-5 h-5 mr-2"></i>
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            <div class="flex">
                <i class="fas fa-check-circle w-5 h-5 mr-2"></i>
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
                        <i class="fas fa-users h-6 w-6 text-gray-600"></i>
                    </div>
                @endif
                <div>
                    <h3 class="text-lg font-medium text-gray-900">Add New Player</h3>
                    <p class="text-sm text-gray-600">{{ $team->name }} - {{ $team->tournament->name ?? 'No Tournament' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Form -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Player Information</h3>
        </div>
        <form action="{{ route('admin.coach.players.store', $team->id) }}" method="POST" class="space-y-6 p-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Player Name -->
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                    <input type="text" 
                           id="full_name" 
                           name="full_name" 
                           value="{{ old('full_name') }}" 
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
                           value="{{ old('jersey_number') }}" 
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
                    <option value="Goalkeeper" {{ old('position') === 'Goalkeeper' ? 'selected' : '' }}>Goalkeeper</option>
                    <option value="Defender" {{ old('position') === 'Defender' ? 'selected' : '' }}>Defender</option>
                    <option value="Midfielder" {{ old('position') === 'Midfielder' ? 'selected' : '' }}>Midfielder</option>
                    <option value="Forward" {{ old('position') === 'Forward' ? 'selected' : '' }}>Forward</option>
                </select>
                @error('position')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 px-6 py-4 bg-gray-50">
                <a href="{{ route('admin.coach.players.index', $team->id) }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-plus w-4 h-4 mr-2 inline"></i>
                    Add Player
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
