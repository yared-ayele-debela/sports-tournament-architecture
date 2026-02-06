@props([
    'name',
    'options' => [],
    'value' => null,
    'placeholder' => 'Select an option',
    'required' => false,
    'error' => null,
    'icon' => null,
])

@php
    $classes = 'block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors';
    
    if ($error) {
        $classes .= ' border-red-500';
    }
    
    if ($icon) {
        $classes .= ' pl-10';
    }
    
    // Handle both array and collection options
    if (is_object($options) && method_exists($options, 'toArray')) {
        $options = $options->toArray();
    }
@endphp

<div class="relative">
    @if($icon)
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <i class="{{ $icon }} text-gray-400"></i>
        </div>
    @endif
    
    <select 
        name="{{ $name }}"
        id="{{ $name }}"
        {{ $attributes->merge(['class' => $classes]) }}
        @if($required) required @endif
    >
        <option value="">{{ $placeholder }}</option>
        @foreach($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" {{ old($name, $value) == $optionValue ? 'selected' : '' }}>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>
</div>

@if($error)
    <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
@endif
