<x-guest-layout>
    <!-- Register Header -->
    <div class="mb-8 text-center">
        <h2 class="text-2xl font-bold text-gray-900">{{ __('Create Account') }}</h2>
        <p class="mt-2 text-sm text-gray-600">{{ __('Join our sports tournament management system') }}</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-6">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Full Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email Address')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Submit Button -->
        <div class="mt-6">
            <x-primary-button class="w-full flex justify-center">
                {{ __('Create Account') }}
            </x-primary-button>
        </div>

        <!-- Login Link -->
        @if (Route::has('login'))
            <div class="mt-6 text-center">
                <span class="text-sm text-gray-600">{{ __('Already have an account?') }}</span>
                <a href="{{ route('login') }}" class="ml-1 text-sm font-medium text-indigo-600 hover:text-indigo-500">
                    {{ __('Sign in') }}
                </a>
            </div>
        @endif
    </form>

    <!-- Account Types Information -->
    <div class="mt-8 p-4 bg-green-50 rounded-lg border border-green-200">
        <h3 class="text-sm font-semibold text-green-900 mb-2">{{ __('Account Types') }}</h3>
        <div class="space-y-2 text-xs text-green-700">
            <div><strong>{{ __('Standard User:') }}</strong> {{ __('Browse tournaments, view matches and standings') }}</div>
            <div><strong>{{ __('Coach:') }}</strong> {{ __('Manage teams and players (requires approval)') }}</div>
            <div><strong>{{ __('Referee:') }}</strong> {{ __('Officiate matches and manage events') }}</div>
            <div><strong>{{ __('Administrator:') }}</strong> {{ __('Full system administration') }}</div>
        </div>
        <p class="mt-3 text-xs text-green-600">{{ __('Note: Coach and Referee accounts require administrator approval. Contact support for role upgrades.') }}</p>
    </div>
</x-guest-layout>
