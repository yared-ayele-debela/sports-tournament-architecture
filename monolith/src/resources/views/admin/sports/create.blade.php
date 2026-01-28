@extends('layouts.admin')

@section('title', 'Create Sport')

@section('content')
<div class="max-w-8xl mx-auto">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Create Sport</h1>
                <p class="text-gray-600 mt-1">Add a new sport to the system</p>
            </div>
            <a href="{{ route('admin.sports.index') }}" class="inline-flex items-center px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Sports
            </a>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <form action="{{ route('admin.sports.store') }}" method="POST" class="p-6">
            @csrf
            
            <div class="grid grid-cols-1 gap-6">
                <!-- Name Field -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Sport Name <span class="text-red-500">*</span>
                    </label>
                   <input type="text"
       id="name"
       name="name"
       value="{{ old('name') }}"
       class="block w-full px-3 py-2 border rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm
              @error('name') border-red-500 @else border-gray-300 @enderror"
       placeholder="Enter sport name"
       required>

                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description Field -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Description
                    </label>
                    <textarea id="description"
          name="description"
          rows="4"
          class="block w-full px-3 py-2 border rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm
                 @error('description') border-red-500 @else border-gray-300 @enderror"
          placeholder="Enter sport description (optional)">{{ old('description') }}</textarea>

                    @error('description')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Optional: Provide additional details about this sport</p>
                </div>

                <!-- Is Active Field -->
                <div class="flex items-center">
                    <input type="checkbox" 
                           id="is_active" 
                           name="is_active" 
                           value="1"
                           {{ old('is_active') ? 'checked' : '' }}
                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-700">
                        Active
                    </label>
                    <p class="mt-1 text-xs text-gray-500">Enable this sport for tournaments and matches</p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="mt-8 flex items-center justify-end space-x-4">
                <a href="{{ route('admin.sports.index') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Cancel
                </a>
                <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Create Sport
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
