@extends('layouts.admin')

@section('title', 'Referee Matches')

@section('content')
<div class="max-w-8xl mx-auto">
    <!-- Referee Header -->
     <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Matches</h1>
                    <p class="text-blue-100">View and manage assigned matches</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="font-medium">{{ Auth::user()->name }}</span>
                    <span class="mx-2">•</span>
                    <span>Referee</span>
                </div>
        </div>
    </div>

    <!-- Matches List -->
     <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="bg-white rounded-lg border border-gray-200">
            @if($matches->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Match
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tournament
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date & Time
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Score
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($matches as $match)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $match->homeTeam->name }} vs {{ $match->awayTeam->name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $match->venue->name ?? 'TBD' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $match->tournament->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ $match->match_date->format('M j, Y') }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $match->match_date->format('H:i') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            {{ $match->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                               ($match->status === 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 
                                               ($match->status === 'cancelled' ? 'bg-red-100 text-red-800' : 
                                               'bg-blue-100 text-blue-800')) }}">
                                            {{ ucfirst(str_replace('_', ' ', $match->status)) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $match->home_score ?? 0 }} - {{ $match->away_score ?? 0 }}
                                        </div>
                                        @if($match->current_minute)
                                            <div class="text-xs text-gray-500">
                                                Minute: {{ $match->current_minute }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                           <a href="{{ route('admin.referee.matches.show', $match) }}"
                                            class="px-3 py-1 text-sm font-medium text-white bg-blue-600 rounded hover:bg-blue-700">
                                            Manage
                                            </a>

                                            <a href="{{ route('admin.referee.events.index', $match) }}"
                                            class="px-3 py-1 text-sm font-medium text-white bg-green-600 rounded hover:bg-green-700">
                                            Events
                                            </a>

                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $matches->links() }}
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No matches found</h3>
                    <p class="mt-2 text-sm text-gray-600">No matches have been assigned to you yet.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
