@extends('layouts.admin')

@section('title', 'Create Team')

@section('content')
<div class="max-w-10xl mx-auto">
    <!-- Page Header -->
    <x-ui.page-header title="Create Team" subtitle="Add a new tournament team">
        <x-slot name="actions">
            <x-ui.button href="{{ route('admin.teams.index') }}" variant="secondary" icon="fas fa-arrow-left">Back to Teams</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <!-- Success/Error Messages -->
    @if(session('success'))
        <x-ui.alert type="success" class="mb-6">
            {{ session('success') }}
        </x-ui.alert>
    @endif

    @if(session('error'))
        <x-ui.alert type="error" class="mb-6">
            {{ session('error') }}
        </x-ui.alert>
    @endif

    <!-- Form -->
    <x-ui.card>
        <form action="{{ route('admin.teams.store') }}" method="POST" enctype="multipart/form-data">
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

                <!-- Team Name Field -->
                <x-ui.form-group label="Team Name" :required="true" :error="$errors->first('name')">
                    <x-ui.input
                        type="text"
                        name="name"
                        placeholder="Enter team name"
                        :required="true"
                        :error="$errors->first('name')"
                        icon="fas fa-users"
                    />
                </x-ui.form-group>

                <!-- Coach Selection Field -->
                <x-ui.form-group label="Assign Coach" :error="$errors->first('coach_id')" help="Select a coach to manage this team. You can assign coaches later if needed.">
                    @php
                        $coachOptions = collect($coaches)->mapWithKeys(function($coach) {
                            return [$coach->id => $coach->name . ' (' . $coach->email . ')'];
                        })->toArray();
                    @endphp
                    <x-ui.select
                        name="coach_id"
                        :options="$coachOptions"
                        placeholder="No Coach Assigned"
                        :error="$errors->first('coach_id')"
                        icon="fas fa-user-tie"
                    />
                </x-ui.form-group>

                <!-- Logo Field -->
                <x-ui.form-group label="Team Logo" :error="$errors->first('logo')" help="Optional: Upload team logo (JPEG, PNG, GIF, SVG - Max 2MB)">
                    <input
                        type="file"
                        name="logo"
                        accept="image/*"
                        class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors @error('logo') border-red-500 @enderror"
                    >
                </x-ui.form-group>
            </div>

            <!-- Form Actions -->
            <div class="mt-8 flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                <x-ui.button href="{{ route('admin.teams.index') }}" variant="secondary" icon="fas fa-times">Cancel</x-ui.button>
                <x-ui.button type="submit" variant="primary" icon="fas fa-save">Create Team</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
