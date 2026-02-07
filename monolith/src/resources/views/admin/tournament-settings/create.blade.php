@extends('layouts.admin')

@section('title', 'Create Tournament Settings')

@section('content')
<div class="max-w-10xl mx-auto">
    <!-- Page Header -->
    <x-ui.page-header title="Create Tournament Settings" subtitle="Configure match duration and daily schedule">
        <x-slot name="actions">
            <x-ui.button href="{{ route('admin.tournament-settings.index') }}" variant="secondary" icon="fas fa-arrow-left">Back to Settings</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <!-- Form -->
    <x-ui.card>
        <form action="{{ route('admin.tournament-settings.store') }}" method="POST">
            @csrf

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
                        value="{{ old('match_duration') }}"
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
                        value="{{ old('win_rest_time') }}"
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
                        value="{{ old('daily_start_time') }}"
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
                        value="{{ old('daily_end_time') }}"
                        :required="true"
                        :error="$errors->first('daily_end_time')"
                        icon="fas fa-moon"
                    />
                </x-ui.form-group>
            </div>

            <!-- Form Actions -->
            <div class="mt-8 flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                <x-ui.button href="{{ route('admin.tournament-settings.index') }}" variant="secondary" icon="fas fa-times">Cancel</x-ui.button>
                <x-ui.button type="submit" variant="primary" icon="fas fa-save">Create Settings</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
