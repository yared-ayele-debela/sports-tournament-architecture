@extends('layouts.admin')

@section('title', $team->name)

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

    <!-- Team Details -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Team Details</h3>
                <div class="flex space-x-2">
                    <a href="{{ route('admin.coach.teams.index') }}" class="inline-flex items-center px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7 7m-7 4h14a2 2 0 002-2v-4a2 2 0 00-2-2H6a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4z" />
                        </svg>
                        Back to Teams
                    </a>
                    <a href="{{ route('admin.coach.teams.edit', $team->id) }}" class="inline-flex items-center px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-4h-4m0 0l4 4m-4 0v6m0 0l4 4" />
                        </svg>
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
                                <svg class="w-16 h-16 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.356-1.857" />
                                </svg>
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
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-4H8m12 4v16m0 0h8" />
                    </svg>
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
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('admin.coach.players.edit', [$team->id, $player->id]) }}" class="text-indigo-600 hover:text-indigo-900">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-4h-4m0 0l4 4m-4 0v6m0 0l4 4" />
                                            </svg>
                                        </a>
                                        <form action="{{ route('admin.coach.players.destroy', [$team->id, $player->id]) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this player?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m0 0l4 4m-4 0v6m0 0l4 4" />
                                                </svg>
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
                                <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.356-1.857" />
                                </svg>
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
