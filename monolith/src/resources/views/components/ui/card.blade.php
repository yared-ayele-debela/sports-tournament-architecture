@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'actions' => null,
    'padding' => true,
    'shadow' => true,
])

@php
    $cardClasses = 'bg-white rounded-lg border border-gray-200';
    if ($shadow) {
        $cardClasses .= ' shadow-sm hover:shadow-md transition-shadow duration-200';
    }
@endphp

<div {{ $attributes->merge(['class' => $cardClasses]) }}>
    @if($title || $subtitle || $icon || $actions)
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                @if($icon)
                    <div class="flex-shrink-0 bg-indigo-100 rounded-lg p-2">
                        <i class="{{ $icon }} text-indigo-600"></i>
                    </div>
                @endif
                <div>
                    @if($title)
                        <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
                    @endif
                    @if($subtitle)
                        <p class="text-sm text-gray-600 mt-0.5">{{ $subtitle }}</p>
                    @endif
                </div>
            </div>
            @if($actions)
                <div class="flex items-center space-x-2">
                    {{ $actions }}
                </div>
            @endif
        </div>
    @endif
    
    <div class="{{ $padding ? 'p-6' : '' }}">
        {{ $slot }}
    </div>
</div>
