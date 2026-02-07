@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
<div class="max-w-10xl mx-auto">
    <!-- Page Header -->
    <x-ui.page-header title="Edit User" subtitle="Update user information and role assignment">
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
        <form action="{{ route('admin.users.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Name Field -->
                <x-ui.form-group label="Full Name" :required="true" :error="$errors->first('name')">
                    <x-ui.input
                        type="text"
                        name="name"
                        value="{{ old('name', $user->name) }}"
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
                        value="{{ old('email', $user->email) }}"
                        placeholder="Enter email address"
                        :required="true"
                        :error="$errors->first('email')"
                        icon="fas fa-envelope"
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
                        :value="$userRole"
                        placeholder="Select a role"
                        :required="true"
                        :error="$errors->first('role')"
                        icon="fas fa-user-tag"
                    />
                </x-ui.form-group>
            </div>

            <!-- Password Section -->
            <div class="mt-8 pt-8 border-t border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-lock mr-2 text-gray-600"></i>
                    Change Password (Optional)
                </h3>
                <p class="text-sm text-gray-600 mb-6">Leave blank to keep current password.</p>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- New Password Field -->
                    <x-ui.form-group label="New Password" :error="$errors->first('password')">
                        <x-ui.input
                            type="password"
                            name="password"
                            placeholder="Enter new password (optional)"
                            :error="$errors->first('password')"
                            icon="fas fa-key"
                        />
                    </x-ui.form-group>

                    <!-- Confirm New Password Field -->
                    <x-ui.form-group label="Confirm New Password" :error="$errors->first('password_confirmation')">
                        <x-ui.input
                            type="password"
                            name="password_confirmation"
                            placeholder="Confirm new password"
                            :error="$errors->first('password_confirmation')"
                            icon="fas fa-key"
                        />
                    </x-ui.form-group>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="mt-8 flex items-center justify-end space-x-3">
                <x-ui.button href="{{ route('admin.users.index') }}" variant="secondary">Cancel</x-ui.button>
                <x-ui.button type="submit" variant="primary" icon="fas fa-save">Update User</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <!-- User Information -->
    <x-ui.card class="mt-6">
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                <i class="fas fa-info-circle mr-2"></i>
                User Information
            </h3>
            <div class="space-y-2 text-sm text-gray-700">
                <div class="flex items-center">
                    <i class="fas fa-user-tag mr-2 text-gray-500 w-4"></i>
                    <strong>Current Role:</strong> {{ ucfirst($userRole) }}
                </div>
                <div class="flex items-center">
                    <i class="fas fa-calendar mr-2 text-gray-500 w-4"></i>
                    <strong>Account Created:</strong> {{ $user->created_at->format('M j, Y g:i A') }}
                </div>
                <div class="flex items-center">
                    <i class="fas fa-clock mr-2 text-gray-500 w-4"></i>
                    <strong>Last Updated:</strong> {{ $user->updated_at->format('M j, Y g:i A') }}
                </div>
                <div class="flex items-center">
                    <i class="fas fa-envelope-check mr-2 text-gray-500 w-4"></i>
                    <strong>Email Verified:</strong> {{ $user->email_verified_at ? $user->email_verified_at->format('M j, Y') : 'Not verified' }}
                </div>
            </div>
        </div>
    </x-ui.card>
</div>
@endsection
