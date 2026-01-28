<x-guest-layout>
    <!-- Reset Password Header -->
    <div class="mb-8 text-center">
        <h2 class="text-2xl font-bold text-gray-900">{{ __('Reset Password') }}</h2>
        <p class="mt-2 text-sm text-gray-600">{{ __('Create a new secure password for your account') }}</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-6">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}" />

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email Address')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('New Password')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm New Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Submit Button -->
        <div class="mt-6">
            <x-primary-button class="w-full flex justify-center">
                {{ __('Reset Password') }}
            </x-primary-button>
        </div>

        <!-- Back to Login -->
        @if (Route::has('login'))
            <div class="mt-6 text-center">
                <span class="text-sm text-gray-600">{{ __('Remember your password?') }}</span>
                <a href="{{ route('login') }}" class="ml-1 text-sm font-medium text-indigo-600 hover:text-indigo-500">
                    {{ __('Sign in') }}
                </a>
            </div>
        @endif
    </form>

    <!-- Security Tips -->
    <div class="mt-8 p-4 bg-red-50 rounded-lg border border-red-200">
        <h3 class="text-sm font-semibold text-red-900 mb-2">{{ __('Security Tips') }}</h3>
        <div class="space-y-2 text-xs text-red-700">
            <div>• {{ __('Use at least 8 characters') }}</div>
            <div>• {{ __('Include uppercase, lowercase, numbers, and symbols') }}</div>
            <div>• {{ __('Avoid using personal information') }}</div>
            <div>• {{ __('Don\'t reuse passwords from other accounts') }}</div>
        </div>
    </div>
</x-guest-layout>
