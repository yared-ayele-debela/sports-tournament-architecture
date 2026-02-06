@props([
    'type' => 'text',
    'name',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'error' => null,
    'icon' => null,
])

@php
    $classes = 'block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors';
    
    if ($error) {
        $classes .= ' border-red-500';
    }
    
    if ($icon) {
        $classes .= ' pl-10';
    }
@endphp

<div class="relative">
    @if($icon)
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <i class="{{ $icon }} text-gray-400"></i>
        </div>
    @endif
    
    <input 
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ old($name, $value) }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => $classes]) }}
        @if($required) required @endif
    />
</div>

@if($error)
    <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
@endif
