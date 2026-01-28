@extends('layouts.admin')

@section('title', $tournament->name)

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
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $tournament->name }}</h1>
                <p class="text-gray-600 mt-1">Tournament details and management</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.tournaments.index') }}" class="inline-flex items-center px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Tournaments
                </a>
                
                <a href="{{ route('admin.tournaments.edit', $tournament->id) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-4h-4v4m0 0l4-4m-4 0v6m0 0l4 4" />
                    </svg>
                    Edit Tournament
                </a>
            </div>
        </div>
    </div>

    <!-- Tournament Details -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Main Information -->
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Tournament Information</h3>
                        <dl class="space-y-3">
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Name</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ $tournament->name }}</dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Sport</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ $tournament->sport->name ?? 'Not assigned' }}</dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd>
                                    <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full 
                                        {{ $tournament->status === 'active' ? 'bg-green-100 text-green-800' : 
                                           ($tournament->status === 'completed' ? 'bg-blue-100 text-blue-800' : 
                                           ($tournament->status === 'cancelled' ? 'bg-red-100 text-red-800' : 
                                           'bg-gray-100 text-gray-800')) }}">
                                        {{ ucfirst($tournament->status) }}
                                    </span>
                                </dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Description</dt>
                                <dd class="text-sm text-gray-900">{{ $tournament->description ?? 'No description provided' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Date Information</h3>
                        <dl class="space-y-3">
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Start Date</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ $tournament->start_date->format('F j, Y') }}</dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">End Date</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ $tournament->end_date->format('F j, Y') }}</dd>
                            </div>
                            @if($tournament->registration_deadline)
                                <div class="flex justify-between py-2 border-b border-gray-200">
                                    <dt class="text-sm font-medium text-gray-500">Registration Deadline</dt>
                                    <dd class="text-sm text-gray-900 font-medium">{{ $tournament->registration_deadline->format('F j, Y') }}</dd>
                                </div>
                            @endif
                            <div class="flex justify-between py-2">
                                <dt class="text-sm font-medium text-gray-500">Duration</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    {{ $tournament->start_date->diffInDays($tournament->end_date) }} days
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Tournament Statistics</h3>
                        <dl class="space-y-3">
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Maximum Teams</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ $tournament->max_teams ?? 'Unlimited' }}</dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Registered Teams</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ $tournament->teams_count ?? 0 }}</dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Created</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ $tournament->created_at->format('F j, Y H:i') }}</dd>
                            </div>
                            <div class="flex justify-between py-2">
                                <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ $tournament->updated_at->format('F j, Y H:i') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Actions -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <a href="{{ route('admin.tournaments.edit', $tournament->id) }}" class="block w-full text-center px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-4h-4v4m0 0l4-4m-4 0v6m0 0l4 4" />
                                </svg>
                                Edit Tournament
                            </a>
                            
                            <form action="{{ route('admin.tournaments.schedule-matches', $tournament->id) }}" method="POST" onsubmit="return confirm('This will generate a round-robin schedule for all registered teams. Are you sure?')">
                                @csrf
                                <button type="submit" class="block w-full text-center px-4 py-3 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2" />
                                    </svg>
                                    Generate Schedule
                                </button>
                            </form>
                            
                            <form action="{{ route('admin.tournaments.destroy', $tournament->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this tournament? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="block w-full text-center px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m0 0L4-4m-4 0v6m0 0L4 4" />
                                    </svg>
                                    Delete Tournament
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Registered Teams -->
    @if($tournament->teams && $tournament->teams->count() > 0)
        <div class="mt-8 bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Registered Teams</h3>
                <p class="text-sm text-gray-600 mt-1">Teams currently registered for this tournament</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Team Name
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Captain
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Players
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Registered
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($tournament->teams as $team)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $team->name }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">{{ $team->captain?->name ?? 'No captain' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">{{ $team->players_count ?? 0 }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500">{{ $team->pivot->created_at
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="mt-8 bg-gray-50 rounded-lg p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900">No Teams Registered</h3>
            <p class="mt-1 text-gray-600">Teams haven't registered for this tournament yet.</p>
        </div>
    @endif
</div>
@endsection
