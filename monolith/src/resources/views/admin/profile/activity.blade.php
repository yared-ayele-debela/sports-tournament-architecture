@extends('layouts.admin')

@section('title', 'Account Activity')

@section('content')
<div class="max-w-8xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Account Activity</h1>
                <p class="text-gray-600 mt-1">View your recent account activity and login history</p>
            </div>
            <a href="{{ route('admin.profile.edit') }}" 
               class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">
                Back to Profile
            </a>
        </div>
    </div>

    <!-- User Info Card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center space-x-4">
            <div class="w-16 h-16 bg-indigo-600 rounded-full flex items-center justify-center">
                <span class="text-white font-semibold text-xl">{{ substr($user->name, 0, 1) }}</span>
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900">{{ $user->name }}</h2>
                <p class="text-gray-600">{{ $user->email }}</p>
                <p class="text-sm text-gray-500 mt-1">Member since {{ $user->created_at->format('F j, Y') }}</p>
            </div>
        </div>
    </div>

    <!-- Activity Log -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-6">Recent Activity</h2>
        
        @if($activities->count() > 0)
            <div class="space-y-4">
                @foreach($activities as $activity)
                    <div class="flex items-start space-x-3 p-4 border border-gray-200 rounded-lg hover:bg-gray-50">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                @switch($activity['action'])
                                    @case('Account Created')
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                        </svg>
                                        @break
                                    @default
                                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                @endswitch
                            </div>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-medium text-gray-900">{{ $activity['action'] }}</h3>
                                <span class="text-xs text-gray-500">{{ $activity['created_at']->format('M j, Y H:i') }}</span>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">{{ $activity['description'] }}</p>
                            @if(isset($activity['ip_address']))
                                <p class="text-xs text-gray-500 mt-1">IP: {{ $activity['ip_address'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-gray-500">No activity recorded yet.</p>
            </div>
        @endif
    </div>

    <!-- Security Information -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mt-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Security Information</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-900 mb-3">Login Security</h3>
                <div class="space-y-2">
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-600">Email Verified</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $user->email_verified_at ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                            {{ $user->email_verified_at ? 'Verified' : 'Not Verified' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-600">Two-Factor Auth</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                            Not Enabled
                        </span>
                    </div>
                </div>
            </div>
            
            <div>
                <h3 class="text-sm font-medium text-gray-900 mb-3">Account Status</h3>
                <div class="space-y-2">
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-600">Account Status</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                            Active
                        </span>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-100">
                        <span class="text-sm text-gray-600">Last Login</span>
                        <span class="text-sm text-gray-900">
                            {{ now()->format('M j, Y H:i') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex">
                <svg class="w-5 h-5 text-blue-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <p class="text-sm text-blue-800">
                    <strong>Security Tip:</strong> Enable two-factor authentication for an extra layer of security on your account.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
