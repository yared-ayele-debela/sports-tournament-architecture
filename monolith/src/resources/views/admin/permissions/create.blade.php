@extends('layouts.admin')

@section('title', 'Create Permission')

@section('content')
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">{{ __('Create Permission') }}</h1>
        <p class="mt-2 text-sm text-gray-600">{{ __('Create a new system permission with appropriate description.') }}</p>
    </div>

    <!-- Form -->
    <div class="bg-white shadow sm:rounded-lg">
        <form method="POST" action="{{ route('admin.permissions.store') }}" class="space-y-6">
            @csrf

            <!-- Permission Name -->
            <div>
                <x-input-label for="name" :value="__('Permission Name')" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="off" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                <p class="mt-1 text-sm text-gray-500">{{ __('Use lowercase letters, numbers, and underscores only.') }}</p>
            </div>

            <!-- Description -->
            <div>
                <x-input-label for="description" :value="__('Description')" />
                <textarea id="description" name="description" rows="4" 
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                          placeholder="{{ __('Describe what this permission allows users to do...') }}">{{ old('description') }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                <p class="mt-1 text-sm text-gray-500">{{ __('Optional: Help administrators understand what this permission controls.') }}</p>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.permissions.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    {{ __('Cancel') }}
                </a>
                <x-primary-button>
                    {{ __('Create Permission') }}
                </x-primary-button>
            </div>
        </form>
    </div>

    <!-- Permission Guidelines -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Naming Conventions -->
        <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
            <h3 class="text-sm font-semibold text-blue-900 mb-2">{{ __('Naming Conventions') }}</h3>
            <div class="space-y-2 text-xs text-blue-700">
                <div><strong>{{ __('Format:') }}</strong> lowercase_with_underscores</div>
                <div><strong>{{ __('Examples:') }}</strong> manage_users, view_reports, edit_matches</div>
                <div><strong>{{ __('Categories:') }}</strong> manage_, view_, create_, delete_, edit_</div>
                <div><strong>{{ __('Resources:') }}</strong> users, matches, tournaments, teams, reports</div>
            </div>
        </div>

        <!-- System Permissions -->
        <div class="p-4 bg-purple-50 rounded-lg border border-purple-200">
            <h3 class="text-sm font-semibold text-purple-900 mb-2">{{ __('System Permissions') }}</h3>
            <div class="space-y-2 text-xs text-purple-700">
                <div><strong>manage_users:</strong> {{ __('Full user management') }}</div>
                <div><strong>manage_matches:</strong> {{ __('Match creation and editing') }}</div>
                <div><strong>manage_tournaments:</strong> {{ __('Tournament administration') }}</div>
                <div><strong>view_reports:</strong> {{ __('Access to reports and analytics') }}</div>
            </div>
            <p class="mt-3 text-xs text-purple-600">{{ __('These are core system permissions that control major functionality.') }}</p>
        </div>
    </div>

    <!-- Examples Section -->
    <div class="mt-8 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
        <h3 class="text-sm font-semibold text-yellow-900 mb-2">{{ __('Permission Examples') }}</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-yellow-700">
            <div>
                <strong>{{ __('User Management:') }}</strong>
                <ul class="mt-1 ml-4 list-disc">
                    <li>create_users</li>
                    <li>edit_users</li>
                    <li>delete_users</li>
                    <li>manage_user_roles</li>
                </ul>
            </div>
            <div>
                <strong>{{ __('Content Management:') }}</strong>
                <ul class="mt-1 ml-4 list-disc">
                    <li>moderate_content</li>
                    <li>publish_articles</li>
                    <li>manage_comments</li>
                    <li>edit_pages</li>
                </ul>
            </div>
            <div>
                <strong>{{ __('Data Access:') }}</strong>
                <ul class="mt-1 ml-4 list-disc">
                    <li>export_data</li>
                    <li>import_data</li>
                    <li>view_analytics</li>
                    <li>access_api</li>
                </ul>
            </div>
            <div>
                <strong>{{ __('System Admin:') }}</strong>
                <ul class="mt-1 ml-4 list-disc">
                    <li>manage_settings</li>
                    <li>view_logs</li>
                    <li>backup_system</li>
                    <li>system_maintenance</li>
                </ul>
            </div>
        </div>
        <p class="mt-3 text-xs text-yellow-600">{{ __('Note: Permissions are system-level and control access to specific functionality.') }}</p>
    </div>
@endsection
