@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-red-600 px-6 py-4">
            <div class="flex items-center">
                <svg class="w-8 h-8 text-white mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
                <h1 class="text-2xl font-bold text-white">Access Denied</h1>
            </div>
        </div>
        
        <!-- Content -->
        <div class="p-6">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Administrator Access Required</h2>
                <p class="text-gray-600 mb-6">
                    You don't have permission to access the admin panel. This area is restricted to users with Administrator role only.
                </p>
                
                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                    <h3 class="text-sm font-medium text-gray-900 mb-2">Current User Information:</h3>
                    <div class="text-sm text-gray-600 space-y-1">
                        <p><strong>Name:</strong> {{ Auth::user()->name }}</p>
                        <p><strong>Email:</strong> {{ Auth::user()->email }}</p>
                        @if(Auth::user()->roles->isNotEmpty())
                            <p><strong>Roles:</strong> {{ Auth::user()->roles->pluck('name')->join(', ') }}</p>
                        @else
                            <p><strong>Roles:</strong> No roles assigned</p>
                        @endif
                    </div>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <x-ui.button href="{{ route('admin.dashboard') }}" variant="primary" icon="fas fa-home">
                        Go to Dashboard
                    </x-ui.button>
                    
                    <x-ui.button href="{{ route('admin.profile.edit') }}" variant="default" icon="fas fa-user">
                        Edit Profile
                    </x-ui.button>
                    
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <x-ui.button type="submit" variant="default" icon="fas fa-sign-out-alt">
                            Sign Out
                        </x-ui.button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Help Section -->
    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
        <h3 class="text-lg font-medium text-blue-900 mb-2">Need Admin Access?</h3>
        <p class="text-blue-700 text-sm mb-4">
            If you believe you should have administrator access, please contact your system administrator to request the appropriate role assignment.
        </p>
        <div class="text-sm text-blue-600">
            <p class="mb-1"><strong>Required Role:</strong> Administrator</p>
            <p><strong>Contact:</strong> System Administrator</p>
        </div>
    </div>
</div>
@endsection
