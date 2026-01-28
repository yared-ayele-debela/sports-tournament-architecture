@extends('layouts.admin')

@section('title', 'Create User')

@section('content')
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">{{ __('Create User') }}</h1>
        <p class="mt-2 text-sm text-gray-600">{{ __('Create a new user account and assign appropriate role.') }}</p>
    </div>

    <!-- Form -->
    <div class="bg-white shadow sm:rounded-lg">
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-6">
            @csrf

            <!-- Name -->
            <div>
                <x-input-label for="name" :value="__('Full Name')" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <!-- Email -->
            <div>
                <x-input-label for="email" :value="__('Email Address')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <!-- Password -->
            <div>
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <!-- Confirm Password -->
            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <!-- Role -->
            <div>
                <x-input-label for="role" :value="__('Role')" />
                <select id="role" name="role" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    @foreach($roles as $role)
                        <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                            {{ ucfirst($role->name) }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('role')" class="mt-2" />
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.users.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    {{ __('Cancel') }}
                </a>
                <x-primary-button>
                    {{ __('Create User') }}
                </x-primary-button>
            </div>
        </form>
    </div>

    <!-- Role Information -->
    <div class="mt-8 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <h3 class="text-sm font-semibold text-blue-900 mb-2">{{ __('User Roles') }}</h3>
        <div class="space-y-2 text-xs text-blue-700">
            <div><strong>{{ __('Admin:') }}</strong> {{ __('Full system access, can manage all users and settings') }}</div>
            <div><strong>{{ __('Referee:') }}</strong> {{ __('Can officiate matches and manage events') }}</div>
            <div><strong>{{ __('Coach:') }}</strong> {{ __('Can manage assigned teams and players') }}</div>
            <div><strong>{{ __('User:') }}</strong> {{ __('Basic access, can view public content') }}</div>
        </div>
        <p class="mt-3 text-xs text-blue-600">{{ __('Note: Assign appropriate role based on user\'s responsibilities.') }}</p>
    </div>
@endsection