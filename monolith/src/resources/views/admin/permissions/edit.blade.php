@extends('layouts.admin')

@section('title', 'Edit Permission')

@section('content')
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">{{ __('Edit Permission') }}</h1>
        <p class="mt-2 text-sm text-gray-600">{{ __('Update permission information and description.') }}</p>
    </div>

    <!-- Form -->
    <div class="bg-white shadow sm:rounded-lg">
        <form method="POST" action="{{ route('admin.permissions.update', $permission->id) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Permission Name -->
            <div>
                <x-input-label for="name" :value="__('Permission Name')" />
                <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" value="{{ old('name', $permission->name) }}" required autofocus autocomplete="off" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                <p class="mt-1 text-sm text-gray-500">{{ __('Use lowercase letters, numbers, and underscores only.') }}</p>
            </div>

            <!-- Description -->
            <div>
                <x-input-label for="description" :value="__('Description')" />
                <textarea id="description" name="description" rows="4" 
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                          placeholder="{{ __('Describe what this permission allows users to do...') }}">{{ old('description', $permission->description) }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
                <p class="mt-1 text-sm text-gray-500">{{ __('Optional: Help administrators understand what this permission controls.') }}</p>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.permissions.index') }}" class="text-sm text-gray-600 hover:text-gray-900">
                    {{ __('Cancel') }}
                </a>
                <x-primary-button>
                    {{ __('Update Permission') }}
                </x-primary-button>
            </div>
        </form>
    </div>

    <!-- Permission Information -->
    <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Current Details -->
        <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
            <h3 class="text-sm font-semibold text-gray-900 mb-2">{{ __('Permission Details') }}</h3>
            <div class="space-y-2 text-xs text-gray-700">
                <div><strong>{{ __('Current Name:') }}</strong> {{ $permission->name }}</div>
                <div><strong>{{ __('Permission Created:') }}</strong> {{ $permission->created_at->format('M j, Y g:i A') }}</div>
                <div><strong>{{ __('Last Updated:') }}</strong> {{ $permission->updated_at->format('M j, Y g:i A') }}</div>
                <div><strong>{{ __('Roles Assigned:') }}</strong> {{ $permission->roles()->count() }} role(s)</div>
                <div><strong>{{ __('Status:') }}</strong> 
                    @if($permission->roles()->count() > 0)
                        <span class="text-green-600">{{ __('In Use') }}</span>
                    @else
                        <span class="text-orange-600">{{ __('Not Assigned') }}</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Usage Information -->
        <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
            <h3 class="text-sm font-semibold text-blue-900 mb-2">{{ __('Usage Information') }}</h3>
            <div class="space-y-2 text-xs text-blue-700">
                <div><strong>{{ __('Permission Type:') }}</strong> 
                    @if(in_array($permission->name, ['manage_users', 'manage_matches', 'manage_tournaments', 'view_reports']))
                        <span class="text-purple-600">{{ __('System Permission') }}</span>
                    @else
                        <span class="text-green-600">{{ __('Custom Permission') }}</span>
                    @endif
                </div>
                <div><strong>{{ __('Can Be Deleted:') }}</strong> 
                    @if($permission->roles()->count() == 0)
                        <span class="text-green-600">{{ __('Yes - Not assigned to any roles') }}</span>
                    @else
                        <span class="text-red-600">{{ __('No - Assigned to roles') }}</span>
                    @endif
                </div>
                <div><strong>{{ __('Impact Level:') }}</strong> 
                    @if(in_array($permission->name, ['manage_users', 'manage_tournaments']))
                        <span class="text-red-600">{{ __('High - System critical') }}</span>
                    @elseif(in_array($permission->name, ['manage_matches', 'view_reports']))
                        <span class="text-orange-600">{{ __('Medium - Important functionality') }}</span>
                    @else
                        <span class="text-blue-600">{{ __('Low - Custom functionality') }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Warning for Assigned Permissions -->
    @if($permission->roles()->count() > 0)
        <div class="mt-8 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
            <h3 class="text-sm font-semibold text-yellow-900 mb-2">{{ __('Permission In Use') }}</h3>
            <div class="space-y-2 text-xs text-yellow-700">
                <div>• {{ __('This permission is currently assigned to ' . $permission->roles()->count() . ' role(s).') }}</div>
                <div>• {{ __('Modifying this permission will affect all assigned roles.') }}</div>
                <div>• {{ __('Consider the impact before making changes.') }}</div>
                <div>• {{ __('Test changes after updating to ensure functionality works correctly.') }}</div>
            </div>
            <p class="mt-3 text-xs text-yellow-600">{{ __('Note: You cannot delete a permission while it is assigned to roles.') }}</p>
        </div>
    @endif
@endsection
