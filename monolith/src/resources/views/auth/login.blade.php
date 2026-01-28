<x-guest-layout>
    <!-- Login Header -->
    <div class="mb-8 text-center">
        <h2 class="text-2xl font-bold text-gray-900">{{ __('Welcome Back') }}</h2>
        <p class="mt-2 text-sm text-gray-600">{{ __('Sign in to access your dashboard') }}</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email Address')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between mt-4">
            <label for="remember_me" class="flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <!-- Submit Button -->
        <div class="mt-6">
            <x-primary-button class="w-full flex justify-center">
                {{ __('Sign In') }}
            </x-primary-button>
        </div>

        <!-- Register Link -->
        @if (Route::has('register'))
            <div class="mt-6 text-center">
                <span class="text-sm text-gray-600">{{ __('Don\'t have an account?') }}</span>
                <a href="{{ route('register') }}" class="ml-1 text-sm font-medium text-indigo-600 hover:text-indigo-500">
                    {{ __('Sign up') }}
                </a>
            </div>
        @endif
    </form>

    <!-- Role Information -->
    <div class="mt-8 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <h3 class="text-sm font-semibold text-blue-900 mb-2">{{ __('Access Levels') }}</h3>
        <div class="space-y-2 text-xs text-blue-700">
            <div><strong>{{ __('Admin:') }}</strong> {{ __('Full system access and management') }}</div>
            <div><strong>{{ __('Referee:') }}</strong> {{ __('Match officiating and event management') }}</div>
            <div><strong>{{ __('Coach:') }}</strong> {{ __('Team and player management') }}</div>
            <div><strong>{{ __('User:') }}</strong> {{ __('View public tournaments and matches') }}</div>
        </div>
    </div>
</x-guest-layout>
