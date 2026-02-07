@extends('layouts.admin')

@section('title', 'Edit Tournament Settings')

@section('content')
<div class="max-w-10xl mx-auto">
    <!-- Page Header -->
    <x-ui.page-header title="Edit Tournament Settings" subtitle="Update match duration and daily schedule">
        <x-slot name="actions">
            <x-ui.button href="{{ route('admin.tournament-settings.index') }}" variant="secondary" icon="fas fa-arrow-left">Back to Settings</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <!-- Form -->
    <x-ui.card>
        <form action="{{ route('admin.tournament-settings.update', $tournamentSetting->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Tournament Field -->
                <x-ui.form-group label="Tournament" :required="true" :error="$errors->first('tournament_id')">
                    @php
                        $tournamentOptions = collect($tournaments)->mapWithKeys(function($tournament) {
                            return [$tournament->id => $tournament->name];
                        })->toArray();
                    @endphp
                    <x-ui.select
                        name="tournament_id"
                        :options="$tournamentOptions"
                        :value="old('tournament_id', $tournamentSetting->tournament_id)"
                        placeholder="Select a tournament"
                        :required="true"
                        :error="$errors->first('tournament_id')"
                        icon="fas fa-trophy"
                    />
                </x-ui.form-group>

                <!-- Match Duration Field -->
                <x-ui.form-group label="Match Duration (minutes)" :required="true" :error="$errors->first('match_duration')" help="Duration of each match in minutes (1-480)">
                    <x-ui.input
                        type="number"
                        name="match_duration"
                        value="{{ old('match_duration', $tournamentSetting->match_duration) }}"
                        placeholder="Enter match duration in minutes"
                        min="1"
                        max="480"
                        :required="true"
                        :error="$errors->first('match_duration')"
                        icon="fas fa-clock"
                    />
                </x-ui.form-group>

                <!-- Win Rest Time Field -->
                <x-ui.form-group label="Rest Time After Win (minutes)" :required="true" :error="$errors->first('win_rest_time')" help="Rest time between matches after a win (0-60 minutes)">
                    <x-ui.input
                        type="number"
                        name="win_rest_time"
                        value="{{ old('win_rest_time', $tournamentSetting->win_rest_time) }}"
                        placeholder="Enter rest time in minutes"
                        min="0"
                        max="60"
                        :required="true"
                        :error="$errors->first('win_rest_time')"
                        icon="fas fa-pause-circle"
                    />
                </x-ui.form-group>

                <!-- Daily Start Time Field -->
                <x-ui.form-group label="Daily Start Time" :required="true" :error="$errors->first('daily_start_time')" help="Daily match schedule start time">
                    <x-ui.input
                        type="time"
                        name="daily_start_time"
                        value="{{ old('daily_start_time', $tournamentSetting->daily_start_time->format('H:i')) }}"
                        :required="true"
                        :error="$errors->first('daily_start_time')"
                        icon="fas fa-sun"
                    />
                </x-ui.form-group>

                <!-- Daily End Time Field -->
                <x-ui.form-group label="Daily End Time" :required="true" :error="$errors->first('daily_end_time')" help="Daily match schedule end time">
                    <x-ui.input
                        type="time"
                        name="daily_end_time"
                        value="{{ old('daily_end_time', $tournamentSetting->daily_end_time->format('H:i')) }}"
                        :required="true"
                        :error="$errors->first('daily_end_time')"
                        icon="fas fa-moon"
                    />
                </x-ui.form-group>
            </div>

            <!-- Form Actions -->
            <div class="mt-8 flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                <x-ui.button href="{{ route('admin.tournament-settings.index') }}" variant="secondary" icon="fas fa-times">Cancel</x-ui.button>
                <x-ui.button type="submit" variant="primary" icon="fas fa-save">Update Settings</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <!-- Settings Info -->
    <x-ui.card title="Settings Information" icon="fas fa-info-circle" class="mt-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="flex items-center">
                <i class="fas fa-trophy text-indigo-600 mr-2"></i>
                <span class="text-gray-500 mr-2">Tournament:</span>
                <span class="text-gray-900 font-medium">{{ $tournamentSetting->tournament->name ?? 'Not assigned' }}</span>
            </div>
            <div class="flex items-center">
                <i class="fas fa-calendar-plus text-green-600 mr-2"></i>
                <span class="text-gray-500 mr-2">Created:</span>
                <span class="text-gray-900 font-medium">{{ $tournamentSetting->created_at->format('M d, Y H:i') }}</span>
            </div>
            <div class="flex items-center">
                <i class="fas fa-edit text-blue-600 mr-2"></i>
                <span class="text-gray-500 mr-2">Last Updated:</span>
                <span class="text-gray-900 font-medium">{{ $tournamentSetting->updated_at->format('M d, Y H:i') }}</span>
            </div>
            <div class="flex items-center">
                <i class="fas fa-clock text-indigo-600 mr-2"></i>
                <span class="text-gray-500 mr-2">Match Duration:</span>
                <span class="text-gray-900 font-medium">{{ $tournamentSetting->match_duration }} minutes</span>
            </div>
            <div class="flex items-center">
                <i class="fas fa-pause-circle text-indigo-600 mr-2"></i>
                <span class="text-gray-500 mr-2">Rest Time:</span>
                <span class="text-gray-900 font-medium">{{ $tournamentSetting->win_rest_time }} minutes</span>
            </div>
            <div class="flex items-center">
                <i class="fas fa-calendar-alt text-indigo-600 mr-2"></i>
                <span class="text-gray-500 mr-2">Daily Schedule:</span>
                <span class="text-gray-900 font-medium">
                    {{ $tournamentSetting->daily_start_time->format('H:i') }} - {{ $tournamentSetting->daily_end_time->format('H:i') }}
                </span>
            </div>
        </div>
    </x-ui.card>
</div>
@endsection
