@extends('layouts.admin')

@section('title', 'Too Many Requests')

@section('content')
<div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-orange-600 px-6 py-4">
            <div class="flex items-center">
                <i class="fas fa-clock text-white text-2xl mr-3"></i>
                <h1 class="text-2xl font-bold text-white">Too Many Requests</h1>
            </div>
        </div>
        
        <!-- Content -->
        <div class="p-6">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-orange-100 mb-4">
                    <i class="fas fa-hourglass-half text-orange-600 text-3xl"></i>
                </div>
                
                <h2 class="text-2xl font-bold text-gray-900 mb-2">429 - Rate Limit Exceeded</h2>
                <p class="text-gray-600 mb-6">
                    You've made too many requests in a short period. Please wait a moment before trying again.
                </p>
                
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle mr-2"></i>
                        This is a security measure to protect our system. Please try again in a few minutes.
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
