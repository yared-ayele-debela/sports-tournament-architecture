@extends('layouts.admin')

@section('title', 'Create User')

@section('content')
<div class="max-w-10xl mx-auto">
    <!-- Page Header -->
    <x-ui.page-header title="Create User" subtitle="Add a new user account with appropriate role">
        <x-slot name="actions">
            <x-ui.button href="{{ route('admin.users.index') }}" variant="secondary" icon="fas fa-arrow-left">Back to Users</x-ui.button>
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
        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Name Field -->
                <x-ui.form-group label="Full Name" :required="true" :error="$errors->first('name')">
                    <x-ui.input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Enter full name"
                        :required="true"
                        :error="$errors->first('name')"
                        icon="fas fa-user"
                    />
                </x-ui.form-group>

                <!-- Email Field -->
                <x-ui.form-group label="Email Address" :required="true" :error="$errors->first('email')">
                    <x-ui.input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="Enter email address"
                        :required="true"
                        :error="$errors->first('email')"
                        icon="fas fa-envelope"
                    />
                </x-ui.form-group>

                <!-- Password Field -->
                <x-ui.form-group label="Password" :required="true" :error="$errors->first('password')">
                    <x-ui.input
                        type="password"
                        name="password"
                        placeholder="Enter password"
                        :required="true"
                        :error="$errors->first('password')"
                        icon="fas fa-lock"
                    />
                </x-ui.form-group>

                <!-- Confirm Password Field -->
                <x-ui.form-group label="Confirm Password" :required="true" :error="$errors->first('password_confirmation')">
                    <x-ui.input
                        type="password"
                        name="password_confirmation"
                        placeholder="Confirm password"
                        :required="true"
                        :error="$errors->first('password_confirmation')"
                        icon="fas fa-lock"
                    />
                </x-ui.form-group>

                <!-- Role Field -->
                <x-ui.form-group label="Role" :required="true" :error="$errors->first('role')">
                    @php
                        $roleOptions = collect($roles)->mapWithKeys(function($role) {
                            return [$role->name => ucfirst($role->name)];
                        })->toArray();
                    @endphp
                    <x-ui.select
                        name="role"
                        :options="$roleOptions"
                        placeholder="Select a role"
                        :required="true"
                        :error="$errors->first('role')"
                        icon="fas fa-user-tag"
                    />
                </x-ui.form-group>
            </div>

            <!-- Form Actions -->
            <div class="mt-6 flex items-center justify-end space-x-3">
                <x-ui.button href="{{ route('admin.users.index') }}" variant="secondary">Cancel</x-ui.button>
                <x-ui.button type="submit" variant="primary" icon="fas fa-save">Create User</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <!-- Role Information -->
    <x-ui.card class="mt-6">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-blue-900 mb-3 flex items-center">
                <i class="fas fa-info-circle mr-2"></i>
                User Roles Guide
            </h3>
            <div class="space-y-2 text-sm text-blue-800">
                <div class="flex items-start">
                    <i class="fas fa-crown mt-0.5 mr-2 text-blue-600"></i>
                    <div>
                        <strong>Admin:</strong> Full system access, can manage all users and settings
                    </div>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-whistle mt-0.5 mr-2 text-blue-600"></i>
                    <div>
                        <strong>Referee:</strong> Can officiate matches and manage events
                    </div>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-clipboard mt-0.5 mr-2 text-blue-600"></i>
                    <div>
                        <strong>Coach:</strong> Can manage assigned teams and players
                    </div>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-user mt-0.5 mr-2 text-blue-600"></i>
                    <div>
                        <strong>User:</strong> Basic access, can view public content
                    </div>
                </div>
            </div>
            <p class="mt-3 text-xs text-blue-600 italic">
                <i class="fas fa-lightbulb mr-1"></i>
                Note: Assign appropriate role based on user's responsibilities.
            </p>
        </div>
    </x-ui.card>
</div>
@endsection