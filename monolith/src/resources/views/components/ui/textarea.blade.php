@props([
    'name',
    'value' => null,
    'placeholder' => null,
    'required' => false,
    'error' => null,
    'rows' => 3,
])

@php
    $classes = 'block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm transition-colors';
    
    if ($error) {
        $classes .= ' border-red-500';
    }
@endphp

<textarea 
    name="{{ $name }}"
    id="{{ $name }}"
    rows="{{ $rows }}"
    placeholder="{{ $placeholder }}"
    {{ $attributes->merge(['class' => $classes]) }}
    @if($required) required @endif
>{{ old($name, $value) }}</textarea>

@if($error)
    <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
@endif
