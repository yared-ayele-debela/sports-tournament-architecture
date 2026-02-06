@extends('layouts.admin')

@section('title', 'Users Management')

@section('content')
<div class="max-w-10xl mx-auto">
    <!-- Page Header -->
    <x-ui.page-header title="Users" subtitle="Manage system users and their roles">
        @if(auth()->user()->hasPermission('manage_users'))
            <x-slot name="actions">
                <x-ui.button href="{{ route('admin.users.create') }}" variant="primary" icon="fas fa-plus">Create User</x-ui.button>
            </x-slot>
        @endif
    </x-ui.page-header>

    <!-- Search and Filter -->
    <x-ui.card class="mb-6">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap gap-4">
            <!-- Search -->
            <div class="flex-1 min-w-64">
                <x-ui.form-group label="Search">
                    <x-ui.input 
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search by name or email..."
                        icon="fas fa-search"
                    />
                </x-ui.form-group>
            </div>

            <!-- Role Filter -->
            <div class="min-w-48">
                <x-ui.form-group label="Role">
                    @php
                        $roleOptions = collect($roles)->mapWithKeys(function($role) {
                            return [$role->name => ucfirst($role->name)];
                        })->toArray();
                        $roleOptions = ['' => 'All Roles'] + $roleOptions;
                    @endphp
                    <x-ui.select 
                        name="role" 
                        :options="$roleOptions"
                        :value="request('role')"
                        icon="fas fa-user-tag"
                    />
                </x-ui.form-group>
            </div>

            <!-- Submit -->
            <div class="flex items-end">
                <x-ui.button type="submit" variant="secondary" icon="fas fa-filter">Filter</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <!-- Users Table -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Roles</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-user text-indigo-600 text-xs"></i>
                                    </div>
                                    <div class="text-sm font-medium text-gray-900">{{ $user->name }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500">
                                    <i class="fas fa-envelope text-xs mr-1"></i>{{ $user->email }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($user->roles as $role)
                                        <x-ui.badge variant="primary" size="sm">{{ ucfirst($role->name) }}</x-ui.badge>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <i class="fas fa-calendar text-xs mr-1"></i>{{ $user->created_at->format('M j, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center justify-end space-x-2">
                                    @if(auth()->user()->hasPermission('manage_users'))
                                        <a href="{{ route('admin.users.edit', $user->id) }}" class="text-blue-600 hover:text-blue-900 transition-colors" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" onsubmit="return confirm('Are you sure you want to delete this user?')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 transition-colors" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="text-gray-500">
                                    <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-lg font-medium">No users found</p>
                                    <p class="text-sm mt-1">Get started by creating your first user.</p>
                                    @if(auth()->user()->hasPermission('manage_users'))
                                        <div class="mt-4">
                                            <x-ui.button href="{{ route('admin.users.create') }}" variant="primary" icon="fas fa-plus">Create User</x-ui.button>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($users->hasPages())
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection