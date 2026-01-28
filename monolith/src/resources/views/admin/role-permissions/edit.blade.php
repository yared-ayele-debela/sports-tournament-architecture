@extends('layouts.admin')

@section('title', 'Edit Role Permissions')

@section('content')
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ __('Edit Permissions') }}</h1>
                <p class="mt-2 text-sm text-gray-600">{{ __('Manage permissions for role: ') }}<strong>{{ ucfirst($role->name) }}</strong></p>
            </div>
            <a href="{{ route('admin.role-permissions.index') }}" class="inline-flex items-center px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                {{ __('Back to Roles') }}
            </a>
        </div>
    </div>

    <!-- Role Information -->
    <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-gray-900">{{ __('Role Details') }}</h3>
                <div class="mt-2 text-xs text-gray-600">
                    <div><strong>{{ __('Current Permissions:') }}</strong> {{ $role->permissions->count() }} assigned</div>
                    <div><strong>{{ __('Last Updated:') }}</strong> {{ $role->updated_at->format('M j, Y g:i A') }}</div>
                    <div><strong>{{ __('Users with this role:') }}</strong> {{ $role->users()->count() }} user(s)</div>
                </div>
            </div>
            @if(in_array($role->name, ['admin', 'referee', 'coach']))
                <div class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">
                    {{ __('Critical Role') }}
                </div>
            @endif
        </div>
    </div>

    <!-- Permission Assignment Form -->
    <form method="POST" action="{{ route('admin.role-permissions.update', $role->id) }}" class="space-y-8">
        @csrf
        @method('PUT')

        @forelse($groupedPermissions as $category => $permissions)
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $category }} Permissions</h3>
                <div class="space-y-3">
                    @foreach($permissions as $permission)
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input type="checkbox" 
                                       id="permission_{{ $permission->id }}" 
                                       name="permissions[]" 
                                       value="{{ $permission->id }}"
                                       {{ in_array($permission->id, $rolePermissionIds) ? 'checked' : '' }}
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="permission_{{ $permission->id }}" class="font-medium text-gray-700 cursor-pointer">
                                    {{ $permission->name }}
                                </label>
                                @if($permission->description)
                                    <p class="text-gray-500">{{ $permission->description }}</p>
                                @endif
                                @if(in_array($permission->name, ['manage_users', 'manage_tournaments']))
                                    <p class="text-orange-600 text-xs mt-1">
                                        <strong>{{ __('High Impact:') }}</strong> {{ __('This permission grants significant system access.') }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="bg-white shadow rounded-lg p-6 text-center">
                <p class="text-gray-500">{{ __('No permissions available.') }}</p>
            </div>
        @endforelse

        <!-- Submit Section -->
        <div class="bg-white shadow rounded-lg p-6">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    <div>{{ __('Selected permissions:') }} <span id="selected-count" class="font-semibold">{{ count($rolePermissionIds) }}</span></div>
                    <div class="text-xs text-gray-500 mt-1">{{ __('Changes will affect all users with this role.') }}</div>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.role-permissions.index') }}" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                        {{ __('Update Permissions') }}
                    </button>
                </div>
            </div>
        </div>
    </form>

    <!-- Quick Selection Tools -->
    <div class="mt-8 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <h3 class="text-sm font-semibold text-blue-900 mb-3">{{ __('Quick Selection Tools') }}</h3>
        <div class="flex flex-wrap gap-3">
            <button type="button" onclick="selectAllPermissions()" class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                {{ __('Select All') }}
            </button>
            <button type="button" onclick="deselectAllPermissions()" class="px-3 py-1 bg-gray-600 text-white text-xs rounded hover:bg-gray-700">
                {{ __('Deselect All') }}
            </button>
            <button type="button" onclick="selectCategory('Management')" class="px-3 py-1 bg-purple-600 text-white text-xs rounded hover:bg-purple-700">
                {{ __('Select Management') }}
            </button>
            <button type="button" onclick="selectCategory('Viewing')" class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700">
                {{ __('Select Viewing') }}
            </button>
        </div>
        <p class="mt-3 text-xs text-blue-600">{{ __('Use these tools to quickly select or deselect permission groups.') }}</p>
    </div>

    <script>
        // Update selected count
        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('input[name="permissions[]"]:checked');
            document.getElementById('selected-count').textContent = checkboxes.length;
        }

        // Select all permissions
        function selectAllPermissions() {
            const checkboxes = document.querySelectorAll('input[name="permissions[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = true);
            updateSelectedCount();
        }

        // Deselect all permissions
        function deselectAllPermissions() {
            const checkboxes = document.querySelectorAll('input[name="permissions[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = false);
            updateSelectedCount();
        }

        // Select permissions by category
        function selectCategory(category) {
            const categorySection = Array.from(document.querySelectorAll('h3')).find(h3 => h3.textContent.includes(category + ' Permissions'));
            if (categorySection) {
                const checkboxes = categorySection.closest('.bg-white').querySelectorAll('input[name="permissions[]"]');
                checkboxes.forEach(checkbox => checkbox.checked = true);
                updateSelectedCount();
            }
        }

        // Add event listeners to all checkboxes
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('input[name="permissions[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectedCount);
            });
        });
    </script>
@endsection
