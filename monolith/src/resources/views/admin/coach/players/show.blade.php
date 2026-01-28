@extends('layouts.admin')

@section('title', $player->full_name)

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

    <!-- Player Details -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900">Player Details</h3>
                <div class="flex space-x-2">
                    <a href="{{ route('admin.coach.players.index', $team->id) }}" class="inline-flex items-center px-3 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7 7m-7 4h14a2 2 0 002-2v-4a2 2 0 00-2-2H6a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4z" />
                        </svg>
                        Back to Players
                    </a>
                    <a href="{{ route('admin.coach.players.edit', [$team->id, $player->id]) }}" class="inline-flex items-center px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-4h-4m0 0l4 4m-4 0v6m0 0l4 4" />
                        </svg>
                        Edit Player
                    </a>
                </div>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Player Info -->
                <div class="lg:col-span-2">
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Personal Information</h4>
                    <dl class="space-y-3">
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                            <dd class="text-sm text-gray-900 font-medium">{{ $player->full_name }}</dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="text-sm font-medium text-gray-500">Jersey Number</dt>
                            <dd class="text-sm text-gray-900 font-medium">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold bg-indigo-100 text-indigo-800">
                                    {{ $player->jersey_number }}
                                </span>
                            </dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="text-sm font-medium text-gray-500">Position</dt>
                            <dd class="text-sm text-gray-900 font-medium">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $player->position === 'Goalkeeper' ? 'bg-purple-100 text-purple-800' : 
                                       ($player->position === 'Defender' ? 'bg-blue-100 text-blue-800' : 
                                       ($player->position === 'Midfielder' ? 'bg-green-100 text-green-800' : 
                                       'bg-red-100 text-red-800')) }}">
                                    {{ $player->position }}
                                </span>
                            </dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-200">
                            <dt class="text-sm font-medium text-gray-500">Team</dt>
                            <dd class="text-sm text-gray-900 font-medium">{{ $team->name }}</dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-sm font-medium text-gray-500">Added to Team</dt>
                            <dd class="text-sm text-gray-900 font-medium">{{ $player->created_at->format('M d, Y H:i') }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Team Info -->
                <div>
                    <h4 class="text-lg font-medium text-gray-900 mb-4">Team Information</h4>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center mb-4">
                            @if($team->logo)
                                <img src="{{ asset('storage/' . $team->logo) }}" alt="{{ $team->name }}" class="w-16 h-16 rounded-full object-cover mr-4">
                            @else
                                <div class="w-16 h-16 bg-gray-300 rounded-full flex items-center justify-center mr-4">
                                    <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.356-1.857" />
                                    </svg>
                                </div>
                            @endif
                            <div>
                                <h5 class="text-lg font-semibold text-gray-900">{{ $team->name }}</h5>
                                <p class="text-sm text-gray-600">{{ $team->tournament->name ?? 'No Tournament' }}</p>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Total Players:</span>
                                <span class="font-medium text-gray-900">{{ $team->players->count() }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Coach:</span>
                                <span class="font-medium text-gray-900">{{ $team->coach_name ?? 'Not assigned' }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Match Events (if any) -->
    @if($player->matchEvents && $player->matchEvents->count() > 0)
        <div class="mt-8 bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Match Events</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Match
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Event Type
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Minute
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($player->matchEvents as $event)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $event->match->homeTeam->name }} vs {{ $event->match->awayTeam->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                        {{ $event->event_type === 'goal' ? 'bg-green-100 text-green-800' : 
                                           ($event->event_type === 'yellow_card' ? 'bg-yellow-100 text-yellow-800' : 
                                           ($event->event_type === 'red_card' ? 'bg-red-100 text-red-800' : 
                                           'bg-blue-100 text-blue-800')) }}">
                                        {{ ucfirst(str_replace('_', ' ', $event->event_type)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $event->minute }}'
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $event->created_at->format('M d, Y H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
