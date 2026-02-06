@extends('layouts.admin')

@section('title', 'Page Not Found')

@section('content')
<div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-indigo-600 px-6 py-4">
            <div class="flex items-center">
                <i class="fas fa-search text-white text-2xl mr-3"></i>
                <h1 class="text-2xl font-bold text-white">Page Not Found</h1>
            </div>
        </div>
        
        <!-- Content -->
        <div class="p-6">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-indigo-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-indigo-600 text-3xl"></i>
                </div>
                
                <h2 class="text-2xl font-bold text-gray-900 mb-2">404 - Page Not Found</h2>
                <p class="text-gray-600 mb-6">
                    The page you are looking for doesn't exist or has been moved.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <x-ui.button href="{{ route('admin.dashboard') }}" variant="primary" icon="fas fa-home">
                        Go to Dashboard
                    </x-ui.button>
                    
                    <x-ui.button href="javascript:history.back()" variant="default" icon="fas fa-arrow-left">
                        Go Back
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
