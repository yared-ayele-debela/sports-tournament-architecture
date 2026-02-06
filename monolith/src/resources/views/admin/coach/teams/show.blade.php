@extends('layouts.admin')

@section('title', $team->name)

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

    <!-- Team Details -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Team Details</h3>
                <div class="flex space-x-2">
                    <a href="{{ route('admin.coach.teams.index') }}" class="inline-flex items-center px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md transition-colors">
                        <i class="fas fa-arrow-left w-4 h-4 mr-2"></i>
                        Back to Teams
                    </a>
                    <a href="{{ route('admin.coach.teams.edit', $team->id) }}" class="inline-flex items-center px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md transition-colors">
                        <i class="fas fa-edit w-4 h-4 mr-2"></i>
                        Edit Team
                    </a>
                </div>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Basic Information -->
                <div>
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h4>
                    <dl class="space-y-3">
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="text-sm font-medium text-gray-500">Team Name</dt>
                            <dd class="text-sm text-gray-900 font-medium">{{ $team->name }}</dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="text-sm font-medium text-gray-500">Tournament</dt>
                            <dd class="text-sm text-gray-900 font-medium">{{ $team->tournament->name ?? 'No Tournament' }}</dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="text-sm font-medium text-gray-500">Coach Name</dt>
                            <dd class="text-sm text-gray-900 font-medium">{{ $team->coach_name ?? 'Not assigned' }}</dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-sm font-medium text-gray-500">Created</dt>
                            <dd class="text-sm text-gray-900 font-medium">{{ $team->created_at->format('M d, Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Team Logo -->
                <div>
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Team Logo</h4>
                    <div class="flex items-center justify-center">
                        @if($team->logo)
                            <img src="{{ asset('storage/' . $team->logo) }}" alt="{{ $team->name }}" class="w-32 h-32 rounded-lg object-cover shadow-lg">
                        @else
                            <div class="w-32 h-32 bg-gray-300 rounded-lg flex items-center justify-center">
                                <i class="fas fa-users w-16 h-16 text-gray-600"></i>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Players Section -->
    <div class="mt-8 bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Players ({{ $team->players->count() }})</h3>
                <a href="{{ route('admin.coach.players.create', $team->id) }}" class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition-colors">
                    <i class="fas fa-plus w-4 h-4 mr-2"></i>
                    Add Player
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Jersey #
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Name
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Position
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @if($team->players->count() > 0)
                        @foreach($team->players as $player)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $player->jersey_number }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $player->full_name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                        {{ $player->position === 'Goalkeeper' ? 'bg-purple-100 text-purple-800' : 
                                           ($player->position === 'Defender' ? 'bg-blue-100 text-blue-800' : 
                                           ($player->position === 'Midfielder' ? 'bg-green-100 text-green-800' : 
                                           'bg-red-100 text-red-800')) }}">
                                        {{ $player->position }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('admin.coach.players.show', [$team->id, $player->id]) }}" class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-eye w-4 h-4"></i>
                                        </a>
                                        <a href="{{ route('admin.coach.players.edit', [$team->id, $player->id]) }}" class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-edit w-4 h-4"></i>
                                        </a>
                                        <form action="{{ route('admin.coach.players.destroy', [$team->id, $player->id]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this player?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                No players added to this team yet.
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <!-- Coaches Section -->
    @if($team->coaches && $team->coaches->count() > 0)
        <div class="mt-8 bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Coaching Staff</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($team->coaches as $coach)
                        <div class="flex items-center space-x-3 p-3 border border-gray-200 rounded-lg">
                            <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                                <i class="fas fa-user-tie w-6 h-6 text-gray-600"></i>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900">{{ $coach->name }}</div>
                                <div class="text-xs text-gray-500">{{ $coach->email }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
