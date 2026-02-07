@extends('layouts.admin')

@section('title', 'Edit Users')

@section('content')
<!-- Page Header -->
<div class="max-w-10xl mx-auto">

    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">{{ __('Edit User') }}</h1>
        <p class="mt-2 text-sm text-gray-600">{{ __('Update user information and role assignment.') }}</p>
    </div>

    <!-- Form -->
    <div class="bg-white shadow sm:rounded-lg">
        <form method="POST" action="{{ route('admin.users.update', $user->id) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Name -->
            <div>
                <x-input-label :value="__('Full Name')" />
                <x-text-input class="block mt-1 w-full" type="text" name="name" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <!-- Email -->
            <div>
                <x-input-label :value="__('Email Address')" />
                <x-text-input class="block mt-1 w-full" type="email" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Role -->
            <div>
                <x-input-label :value="__('Role')" />
                <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ $userRole == $role->name ? 'selected' : '' }}>
                            {{ ucfirst($role->name) }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('role')" class="mt-2" />
            </div>

            <!-- Password Section -->
            <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Change Password (Optional)') }}</h3>
                <p class="text-sm text-gray-600 mb-4">{{ __('Leave blank to keep current password.') }}</p>

                <!-- New Password -->
                <div>
                    <x-input-label :value="__('New Password')" />
                    <x-text-input class="block mt-1 w-full" type="password" name="password" autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Confirm New Password -->
                <div>
                    <x-input-label :value="__('Confirm New Password')" />
                    <x-text-input class="block mt-1 w-full" type="password" name="password_confirmation" autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    {{ __('Cancel') }}
                </a>
                <x-primary-button>
                    {{ __('Update User') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</div>
    <!-- User Information -->

@endsection
