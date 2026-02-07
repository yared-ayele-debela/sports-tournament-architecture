@extends('layouts.admin')

@section('title', 'Match Report - ' . $match->homeTeam->name . ' vs ' . $match->awayTeam->name)

@section('content')
<div class="max-w-10xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Match Report</h1>
                <p class="text-gray-600">{{ $match->homeTeam->name }} vs {{ $match->awayTeam->name }}</p>
            </div>
            <div class="flex space-x-2">
                <a href="{{ route('admin.referee.reports.edit', $match) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md transition-colors">
                    <i class="fas fa-edit w-4 h-4 mr-2"></i>
                    Edit Report
                </a>
                <a href="{{ route('admin.referee.matches.show', $match) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md transition-colors">
                    <i class="fas fa-arrow-left w-4 h-4 mr-2"></i>
                    Back to Match
                </a>
            </div>
        </div>
    </div>

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
                <div class="font-medium text-lg">{{ $match->home_score ?? 0 }} - {{ $match->away_score ?? 0 }}</div>
            </div>
        </div>
    </div>

    <!-- Report Details -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="mb-6">
            <h2 class="text-lg font-semibold mb-4">Report Details</h2>
            <div class="grid md:grid-cols-2 gap-4 mb-6">
                <div>
                    <div class="text-sm text-gray-600">Referee</div>
                    <div class="font-medium">{{ $report->referee ?? 'N/A' }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-600">Attendance</div>
                    <div class="font-medium">{{ $report->attendance ?? 'N/A' }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-600">Created</div>
                    <div class="font-medium">{{ $report->created_at->format('M j, Y H:i') }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-600">Last Updated</div>
                    <div class="font-medium">{{ $report->updated_at->format('M j, Y H:i') }}</div>
                </div>
            </div>
        </div>

        <div>
            <h3 class="text-md font-semibold mb-3">Match Summary</h3>
            <div class="prose max-w-none">
                <p class="text-gray-700 whitespace-pre-wrap">{{ $report->summary }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
