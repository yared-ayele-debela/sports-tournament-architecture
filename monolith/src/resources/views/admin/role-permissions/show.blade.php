@extends('layouts.admin')

@section('title', 'Role Permissions - ' . ucfirst($role->name))

@section('content')
    <!-- Page Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">{{ __('Role Permissions') }}</h1>
                <p class="mt-2 text-sm text-gray-600">{{ __('Viewing permissions for role: ') }}<strong>{{ ucfirst($role->name) }}</strong></p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.role-permissions.edit', $role->id) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    {{ __('Edit Permissions') }}
                </a>
                <a href="{{ route('admin.role-permissions.index') }}" class="inline-flex items-center px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    {{ __('Back to Roles') }}
                </a>
            </div>
        </div>
    </div>

    <!-- Role Summary -->
    <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">{{ __('Users') }}</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $role->users()->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">{{ __('Permissions') }}</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $role->permissions->count() }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-gray-900">{{ __('Last Updated') }}</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $role->updated_at->format('M j, Y') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Permissions by Category -->
    @forelse($groupedPermissions as $category => $permissions)
        <div class="mb-6">
            <h2 class="text-lg font-medium text-gray-900 mb-3">{{ $category }} ({{ $permissions->count() }})</h2>
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="min-w-full overflow-hidden overflow-x-auto align-middle">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('Permission') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('Description') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('Impact') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($permissions as $permission)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="text-sm font-medium text-gray-900">{{ $permission->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ $permission->description ? $permission->description : __('No description available') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if(in_array($permission->name, ['manage_users', 'manage_tournaments']))
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                {{ __('High') }}
                                            </span>
                                        @elseif(in_array($permission->name, ['manage_matches', 'view_reports']))
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                {{ __('Medium') }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                {{ __('Low') }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @empty
        <div class="bg-white shadow rounded-lg p-6 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No Permissions Assigned') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ __('This role currently has no permissions assigned.') }}</p>
            <div class="mt-6">
                <a href="{{ route('admin.role-permissions.edit', $role->id) }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    {{ __('Assign Permissions') }}
                </a>
            </div>
        </div>
    @endforelse

    <!-- Role Impact Information -->
    @if(in_array($role->name, ['admin', 'referee', 'coach']))
        <div class="mt-8 p-4 bg-yellow-50 rounded-lg border border-yellow-200">
            <h3 class="text-sm font-semibold text-yellow-900 mb-2">{{ __('Critical Role Impact') }}</h3>
            <div class="space-y-2 text-xs text-yellow-700">
                <div>• {{ __('This is a critical system role with significant impact.') }}</div>
                <div>• {{ __('Permission changes affect all users with this role.') }}</div>
                <div>• {{ __('Test changes thoroughly after updating permissions.') }}</div>
                <div>• {{ __('Consider the security implications of each permission.') }}</div>
            </div>
            <p class="mt-3 text-xs text-yellow-600">{{ __('Contact system administrator if unsure about permission assignments.') }}</p>
        </div>
    @endif
@endsection
