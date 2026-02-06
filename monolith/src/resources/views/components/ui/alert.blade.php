@props([
    'type' => 'success', // success, error, warning, info
    'icon' => true,
    'dismissible' => false,
])

@php
    $typeClasses = [
        'success' => 'bg-green-50 border-green-200 text-green-800',
        'error' => 'bg-red-50 border-red-200 text-red-800',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
        'info' => 'bg-blue-50 border-blue-200 text-blue-800',
    ];
    
    $typeIcons = [
        'success' => 'fas fa-check-circle',
        'error' => 'fas fa-exclamation-circle',
        'warning' => 'fas fa-exclamation-triangle',
        'info' => 'fas fa-info-circle',
    ];
    
    $classes = 'border rounded-lg px-4 py-3 ' . $typeClasses[$type];
@endphp

<div {{ $attributes->merge(['class' => $classes]) }} x-data="{ show: true }" x-show="show" x-transition>
    <div class="flex items-start">
        @if($icon)
            <i class="{{ $typeIcons[$type] }} w-5 h-5 mr-2 mt-0.5 flex-shrink-0"></i>
        @endif
        <div class="flex-1">
            {{ $slot }}
        </div>
        @if($dismissible)
            <button @click="show = false" class="ml-4 flex-shrink-0 text-current opacity-50 hover:opacity-75">
                <i class="fas fa-times w-4 h-4"></i>
            </button>
        @endif
    </div>
</div>
