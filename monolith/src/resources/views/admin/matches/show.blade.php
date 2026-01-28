@extends('layouts.admin')

@section('title', $match->homeTeam->name . ' vs ' . $match->awayTeam->name)

@section('content')
<div class="p-6">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $match->homeTeam->name }} vs {{ $match->awayTeam->name }}</h1>
                <p class="text-gray-600 mt-1">Match details and information</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.matches.index') }}" class="inline-flex items-center px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Matches
                </a>
                
                <a href="{{ route('admin.matches.edit', $match->id) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-4h-4v4m0 0l4-4m-4 0v6m0 0l4 4" />
                    </svg>
                    Edit Match
                </a>
            </div>
        </div>
    </div>

    <!-- Match Details -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Main Information -->
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Match Information</h3>
                        <dl class="space-y-3">
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Home Team</dt>
                                <dd class="text-sm text-gray-900 font-medium flex items-center">
                                    @if($match->homeTeam->logo)
                                        <img src="{{ asset('storage/' . $match->homeTeam->logo) }}" alt="{{ $match->homeTeam->name }}" class="h-6 w-6 rounded-full object-cover mr-2">
                                    @else
                                        <div class="h-6 w-6 rounded-full bg-gray-300 flex items-center justify-center mr-2">
                                            <svg class="h-3 w-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                    @endif
                                    {{ $match->homeTeam->name }}
                                </dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Away Team</dt>
                                <dd class="text-sm text-gray-900 font-medium flex items-center">
                                    @if($match->awayTeam->logo)
                                        <img src="{{ asset('storage/' . $match->awayTeam->logo) }}" alt="{{ $match->awayTeam->name }}" class="h-6 w-6 rounded-full object-cover mr-2">
                                    @else
                                        <div class="h-6 w-6 rounded-full bg-gray-300 flex items-center justify-center mr-2">
                                            <svg class="h-3 w-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                    @endif
                                    {{ $match->awayTeam->name }}
                                </dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Tournament</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ $match->tournament->name ?? 'Not assigned' }}</dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Venue</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ $match->venue->name ?? 'Not assigned' }}</dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Date & Time</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ $match->match_date->format('F j, Y H:i') }}</dd>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Round Number</dt>
                                <dd class="text-sm text-gray-900 font-medium">Round {{ $match->round_number }}</dd>
                            </div>
                            <div class="flex justify-between py-2">
                                <dt class="text-sm font-medium text-gray-500">Status</dt>
                                <dd class="text-sm font-medium">
                                                                 <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $match->statusBadgeClasses() }}">
  
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Statistics & Actions -->
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Match Statistics</h3>
                        <dl class="space-y-3">
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Score</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    @if($match->home_score !== null && $match->away_score !== null)
                                        <span class="font-bold">{{ $match->home_score }}</span> - <span class="font-bold">{{ $match->away_score }}</span>
                                    @else
                                        <span class="text-gray-500">Not started</span>
                                    @endif
                                </dd>
                            </div>
                            @if($match->current_minute !== null)
                                <div class="flex justify-between py-2 border-b border-gray-200">
                                    <dt class="text-sm font-medium text-gray-500">Current Minute</dt>
                                    <dd class="text-sm text-gray-900 font-medium">{{ $match->current_minute }}'</dd>
                                </div>
                            @endif
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <dt class="text-sm font-medium text-gray-500">Created</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ $match->created_at->format('F j, Y H:i') }}</dd>
                            </div>
                            <div class="flex justify-between py-2">
                                <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                <dd class="text-sm text-gray-900 font-medium">{{ $match->updated_at->format('F j, Y H:i') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                        <div class="space-y-3">
                            <a href="{{ route('admin.matches.edit', $match->id) }}" class="block w-full text-center px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-4h-4v4m0 0l4-4m-4 0v6m0 0l4 4" />
                                </svg>
                                Edit Match
                            </a>
                            
                            <form action="{{ route('admin.matches.destroy', $match->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this match? This action cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="block w-full text-center px-4 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m0 0L4-4m-4 0v6m0 0L4 4" />
                                    </svg>
                                    Delete Match
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
