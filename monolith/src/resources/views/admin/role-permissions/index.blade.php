@extends('layouts.admin')

@section('title', 'Role Permissions Management')

@section('content')
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">{{ __('Role Permissions') }}</h1>
        <p class="mt-2 text-sm text-gray-600">{{ __('Manage permissions assigned to each role.') }}</p>
    </div>

    <!-- Roles and Permissions Overview -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="min-w-full overflow-hidden overflow-x-auto align-middle">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('Role') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('Permissions Count') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('Key Permissions') }}
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ __('Updated') }}
                        </th>
                        <th scope="col" class="relative px-6 py-3">
                            <span class="sr-only">{{ __('Actions') }}</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($roles as $role)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="text-sm font-medium text-gray-900">{{ ucfirst($role->name) }}</span>
                                    @if(in_array($role->name, ['admin', 'referee', 'coach']))
                                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            {{ __('Critical') }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                    {{ $role->permissions->count() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <div class="max-w-md">
                                    @if($role->permissions->count() > 0)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($role->permissions->take(3) as $permission)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                    {{ $permission->name }}
                                                </span>
                                            @endforeach
                                            @if($role->permissions->count() > 3)
                                                <span class="text-xs text-gray-500">+{{ $role->permissions->count() - 3 }} more</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400">{{ __('No permissions assigned') }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $role->updated_at->format('M j, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('admin.role-permissions.show', $role->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">
                                    {{ __('View') }}
                                </a>
                                <a href="{{ route('admin.role-permissions.edit', $role->id) }}" class="text-green-600 hover:text-green-900">
                                    {{ __('Assign') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                {{ __('No roles found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Permission Categories Overview -->
    <div class="mt-8">
        <h2 class="text-lg font-medium text-gray-900 mb-4">{{ __('Permission Categories') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($groupedPermissions as $category => $permissions)
                <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2">{{ $category }}</h3>
                    <div class="text-xs text-gray-600">
                        <div class="mb-2">
                            <strong>{{ __('Total:') }}</strong> {{ $permissions->count() }} {{ __('permissions') }}
                        </div>
                        <div class="space-y-1">
                            @foreach($permissions->take(3) as $permission)
                                <div class="truncate">{{ $permission->name }}</div>
                            @endforeach
                            @if($permissions->count() > 3)
                                <div class="text-gray-400">{{ __('...and more') }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center text-sm text-gray-500">
                    {{ __('No permissions found.') }}
                </div>
            @endforelse
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-8 p-4 bg-blue-50 rounded-lg border border-blue-200">
        <h3 class="text-sm font-semibold text-blue-900 mb-2">{{ __('Quick Actions') }}</h3>
        <div class="flex flex-wrap gap-4 text-xs text-blue-700">
            @if($adminRole = $roles->where('name', 'admin')->first())
                <div>
                    <strong>{{ __('Admin Role:') }}</strong> 
                    <a href="{{ route('admin.role-permissions.edit', $adminRole->id) }}" class="text-blue-600 hover:underline">
                        {{ __('Configure permissions') }}
                    </a>
                </div>
            @endif
            @if($refereeRole = $roles->where('name', 'referee')->first())
                <div>
                    <strong>{{ __('Referee Role:') }}</strong> 
                    <a href="{{ route('admin.role-permissions.edit', $refereeRole->id) }}" class="text-blue-600 hover:underline">
                        {{ __('Configure permissions') }}
                    </a>
                </div>
            @endif
            @if($coachRole = $roles->where('name', 'coach')->first())
                <div>
                    <strong>{{ __('Coach Role:') }}</strong> 
                    <a href="{{ route('admin.role-permissions.edit', $coachRole->id) }}" class="text-blue-600 hover:underline">
                        {{ __('Configure permissions') }}
                    </a>
                </div>
            @endif
        </div>
        <p class="mt-3 text-xs text-blue-600">{{ __('Click on any role to view or modify its assigned permissions.') }}</p>
    </div>
@endsection
