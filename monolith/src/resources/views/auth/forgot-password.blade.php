<x-guest-layout>
    <!-- Forgot Password Header -->
    <div class="mb-8 text-center">
        <h2 class="text-2xl font-bold text-gray-900">{{ __('Reset Password') }}</h2>
        <p class="mt-2 text-sm text-gray-600">{{ __('No problem. We\'ll email you a reset link.') }}</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email Address')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Submit Button -->
        <div class="mt-6">
            <x-primary-button class="w-full flex justify-center">
                {{ __('Send Reset Link') }}
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

    <!-- Help Information -->
    <div class="mt-8 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
        <h3 class="text-sm font-semibold text-yellow-900 mb-2">{{ __('Need Help?') }}</h3>
        <div class="space-y-2 text-xs text-yellow-700">
            <div>• {{ __('Check your spam folder for the reset email') }}</div>
            <div>• {{ __('Reset links expire after 60 minutes') }}</div>
            <div>• {{ __('Contact support if you continue having issues') }}</div>
        </div>
    </div>
</x-guest-layout>
