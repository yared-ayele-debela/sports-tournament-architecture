@extends('layouts.admin')

@section('title', 'Edit Match Report - ' . $match->homeTeam->name . ' vs ' . $match->awayTeam->name)

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Edit Match Report</h1>
                <p class="text-gray-600">{{ $match->homeTeam->name }} vs {{ $match->awayTeam->name }}</p>
            </div>
            <a href="{{ route('admin.referee.matches.show', $match) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md transition-colors">
                <i class="fas fa-arrow-left w-4 h-4 mr-2"></i>
                Back to Match
            </a>
        </div>
    </div>

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
                <i class="fas fa-exclamation-circle w-5 h-5 mr-2"></i>
                {{ session('error') }}
            </div>
        </div>
    @endif

    <!-- Match Info -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <h2 class="text-lg font-semibold mb-4">Match Information</h2>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <div class="text-sm text-gray-600">Tournament</div>
                <div class="font-medium">{{ $match->tournament->name }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-600">Date & Time</div>
                <div class="font-medium">{{ $match->match_date->format('M j, Y H:i') }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-600">Venue</div>
                <div class="font-medium">{{ $match->venue->name ?? 'TBD' }}</div>
            </div>
            <div>
                <div class="text-sm text-gray-600">Final Score</div>
                <div class="font-medium">{{ $match->home_score ?? 0 }} - {{ $match->away_score ?? 0 }}</div>
            </div>
        </div>
    </div>

    <!-- Report Form -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <form action="{{ route('admin.referee.reports.update', $match) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Summary -->
            <div class="mb-6">
                <label for="summary" class="block text-sm font-medium text-gray-700 mb-2">
                    Match Summary <span class="text-red-500">*</span>
                </label>
                <textarea 
                    id="summary" 
                    name="summary" 
                    rows="10"
                    required
                    minlength="10"
                    maxlength="5000"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('summary') border-red-500 @enderror"
                    placeholder="Provide a detailed summary of the match including key moments, player performances, and any notable incidents...">{{ old('summary', $report->summary) }}</textarea>
                @error('summary')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-sm text-gray-500">Minimum 10 characters, maximum 5000 characters</p>
            </div>

            <!-- Referee Name -->
            <div class="mb-6">
                <label for="referee" class="block text-sm font-medium text-gray-700 mb-2">
                    Referee Name
                </label>
                <input 
                    type="text" 
                    id="referee" 
                    name="referee" 
                    value="{{ old('referee', $report->referee) }}"
                    maxlength="255"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('referee') border-red-500 @enderror"
                    placeholder="Referee name">
                @error('referee')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Attendance -->
            <div class="mb-6">
                <label for="attendance" class="block text-sm font-medium text-gray-700 mb-2">
                    Attendance
                </label>
                <input 
                    type="text" 
                    id="attendance" 
                    name="attendance" 
                    value="{{ old('attendance', $report->attendance) }}"
                    maxlength="255"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm @error('attendance') border-red-500 @enderror"
                    placeholder="e.g., 15,234 or Approximately 15,000">
                @error('attendance')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                <a href="{{ route('admin.referee.matches.show', $match) }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-save w-4 h-4 mr-2 inline"></i>
                    Update Report
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
