@extends('layouts.admin')

@section('title', 'Role Permissions Management')

@section('content')
<div class="max-w-10xl mx-auto">
    <!-- Page Header -->
    <x-ui.page-header 
        title="Role Permissions" 
        subtitle="Manage permissions assigned to each role"
    />

    <!-- Roles and Permissions Overview -->
    <x-ui.card title="Roles & Permissions" icon="fas fa-user-shield" class="mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Permissions Count</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Key Permissions</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Updated</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($roles as $role)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-user-tag text-indigo-600"></i>
                                    </div>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">{{ ucfirst($role->name) }}</div>
                                        @if(in_array($role->name, ['admin', 'referee', 'coach']))
                                            <x-ui.badge variant="error" size="sm" class="mt-1">Critical</x-ui.badge>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <x-ui.badge variant="primary">{{ $role->permissions->count() }}</x-ui.badge>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <div class="max-w-md">
                                    @if($role->permissions->count() > 0)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach($role->permissions->take(3) as $permission)
                                                <x-ui.badge variant="default" size="sm">{{ $permission->name }}</x-ui.badge>
                                            @endforeach
                                            @if($role->permissions->count() > 3)
                                                <span class="text-xs text-gray-500 ml-1">+{{ $role->permissions->count() - 3 }} more</span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-400">No permissions assigned</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <i class="fas fa-calendar text-xs mr-1"></i>{{ $role->updated_at->format('M j, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    @if(auth()->user()->hasPermission('manage_roles'))
                                        <x-ui.button href="{{ route('admin.role-permissions.show', $role->id) }}" variant="info" size="sm" icon="fas fa-eye">View</x-ui.button>
                                        <x-ui.button href="{{ route('admin.role-permissions.edit', $role->id) }}" variant="success" size="sm" icon="fas fa-edit">Assign</x-ui.button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <i class="fas fa-user-shield text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-lg font-medium">No roles found</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <!-- Permission Categories Overview -->
    <x-ui.card title="Permission Categories" icon="fas fa-list" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($groupedPermissions as $category => $permissions)
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-indigo-300 transition-colors">
                    <h3 class="text-sm font-semibold text-gray-900 mb-2 flex items-center">
                        <i class="fas fa-folder text-indigo-600 mr-2"></i>{{ $category }}
                    </h3>
                    <div class="text-xs text-gray-600">
                        <div class="mb-2">
                            <strong>Total:</strong> {{ $permissions->count() }} permissions
                        </div>
                        <div class="space-y-1">
                            @foreach($permissions->take(3) as $permission)
                                <div class="truncate text-gray-700">{{ $permission->name }}</div>
                            @endforeach
                            @if($permissions->count() > 3)
                                <div class="text-gray-400">...and {{ $permissions->count() - 3 }} more</div>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full text-center text-sm text-gray-500 py-8">
                    <i class="fas fa-inbox text-3xl text-gray-300 mb-2"></i>
                    <p>No permissions found</p>
                </div>
            @endforelse
        </div>
    </x-ui.card>

    <!-- Quick Actions -->
    <x-ui.card title="Quick Actions" icon="fas fa-bolt" class="bg-blue-50 border-blue-200">
        <div class="flex flex-wrap gap-4 text-sm">
            @if($adminRole = $roles->where('name', 'admin')->first())
                <div class="flex items-center">
                    <strong class="text-blue-900 mr-2">Admin Role:</strong>
                    <x-ui.button href="{{ route('admin.role-permissions.edit', $adminRole->id) }}" variant="info" size="sm" icon="fas fa-cog">Configure</x-ui.button>
                </div>
            @endif
            @if($refereeRole = $roles->where('name', 'referee')->first())
                <div class="flex items-center">
                    <strong class="text-blue-900 mr-2">Referee Role:</strong>
                    <x-ui.button href="{{ route('admin.role-permissions.edit', $refereeRole->id) }}" variant="info" size="sm" icon="fas fa-cog">Configure</x-ui.button>
                </div>
            @endif
            @if($coachRole = $roles->where('name', 'coach')->first())
                <div class="flex items-center">
                    <strong class="text-blue-900 mr-2">Coach Role:</strong>
                    <x-ui.button href="{{ route('admin.role-permissions.edit', $coachRole->id) }}" variant="info" size="sm" icon="fas fa-cog">Configure</x-ui.button>
                </div>
            @endif
        </div>
        <p class="mt-3 text-xs text-blue-700">Click on any role to view or modify its assigned permissions.</p>
    </x-ui.card>
</div>
@endsection
