@extends('layouts.admin')

@section('title', 'My Teams')

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

    <!-- Teams List -->
    @if($teams->count() > 0)
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">My Teams</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Team
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tournament
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Players
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Coach
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($teams as $team)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        @if($team->logo)
                                            <img src="{{ asset('storage/' . $team->logo) }}" alt="{{ $team->name }}" class="h-8 w-8 rounded-full object-cover mr-3">
                                        @else
                                            <div class="h-8 w-8 bg-gray-300 rounded-full flex items-center justify-center mr-3">
                                                <i class="fas fa-users h-4 w-4 text-gray-600"></i>
                                            </div>
                                        @endif
                                        <div class="text-sm font-medium text-gray-900">{{ $team->name }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $team->tournament->name ?? 'No Tournament' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $team->players->count() }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        @if($team->coaches->count() > 0)
                                            {{ $team->coaches->pluck('name')->implode(', ') }}
                                        @else
                                            Not assigned
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="{{ route('admin.coach.teams.show', $team->id) }}" class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-eye w-4 h-4"></i>
                                        </a>
                                        <a href="{{ route('admin.coach.teams.edit', $team->id) }}" class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-edit w-4 h-4"></i>
                                        </a>
                                        <a href="{{ route('admin.coach.players.index', $team->id) }}" class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-user-friends w-4 h-4"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="bg-gray-50 rounded-lg p-8 text-center">
            <i class="fas fa-users mx-auto h-12 w-12 text-gray-400"></i>
            <h3 class="mt-2 text-lg font-medium text-gray-900">No Teams Assigned</h3>
            <p class="mt-1 text-gray-600">You haven't been assigned to any teams yet. Contact an administrator to get started.</p>
        </div>
    @endif
</div>
@endsection
