@props([
    'title',
    'value',
    'icon',
    'iconColor' => 'indigo', // indigo, green, blue, yellow, red, purple, orange, teal
    'trend' => null, // positive, negative, neutral
    'trendValue' => null,
])

@php
    $iconColors = [
        'indigo' => 'bg-indigo-100 text-indigo-600',
        'green' => 'bg-green-100 text-green-600',
        'blue' => 'bg-blue-100 text-blue-600',
        'yellow' => 'bg-yellow-100 text-yellow-600',
        'red' => 'bg-red-100 text-red-600',
        'purple' => 'bg-purple-100 text-purple-600',
        'orange' => 'bg-orange-100 text-orange-600',
        'teal' => 'bg-teal-100 text-teal-600',
    ];
@endphp

<div class="bg-white rounded-lg border border-gray-200 p-6 shadow-sm hover:shadow-md transition-shadow duration-200">
    <div class="flex items-center">
        <div class="flex-shrink-0 {{ $iconColors[$iconColor] }} rounded-lg p-3">
            <i class="{{ $icon }} w-6 h-6"></i>
        </div>
        <div class="ml-4 flex-1">
            <p class="text-sm font-medium text-gray-600">{{ $title }}</p>
            <p class="text-2xl font-semibold text-gray-900 mt-1">{{ $value }}</p>
            @if($trend && $trendValue)
                <div class="mt-2 flex items-center text-xs">
                    @if($trend === 'positive')
                        <i class="fas fa-arrow-up text-green-600 mr-1"></i>
                        <span class="text-green-600 font-medium">{{ $trendValue }}</span>
                    @elseif($trend === 'negative')
                        <i class="fas fa-arrow-down text-red-600 mr-1"></i>
                        <span class="text-red-600 font-medium">{{ $trendValue }}</span>
                    @else
                        <i class="fas fa-minus text-gray-600 mr-1"></i>
                        <span class="text-gray-600 font-medium">{{ $trendValue }}</span>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
