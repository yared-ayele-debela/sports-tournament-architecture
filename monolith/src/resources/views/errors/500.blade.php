@extends('layouts.admin')

@section('title', 'Server Error')

@section('content')
<div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-red-600 px-6 py-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-white text-2xl mr-3"></i>
                <h1 class="text-2xl font-bold text-white">Server Error</h1>
            </div>
        </div>
        
        <!-- Content -->
        <div class="p-6">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-server text-red-600 text-3xl"></i>
                </div>
                
                <h2 class="text-2xl font-bold text-gray-900 mb-2">500 - Internal Server Error</h2>
                <p class="text-gray-600 mb-6">
                    Something went wrong on our end. We've been notified and are working to fix the issue.
                </p>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <p class="text-sm text-yellow-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        If this problem persists, please contact support with the error details.
                    </p>
                </div>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <x-ui.button href="{{ route('admin.dashboard') }}" variant="primary" icon="fas fa-home">
                        Go to Dashboard
                    </x-ui.button>
                    
                    <x-ui.button href="javascript:location.reload()" variant="default" icon="fas fa-redo">
                        Try Again
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
