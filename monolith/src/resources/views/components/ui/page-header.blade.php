@props([
    'title',
    'subtitle' => null,
])

<div class="mb-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $title }}</h1>
            @if($subtitle)
                <p class="text-gray-600 mt-1">{{ $subtitle }}</p>
            @endif
        </div>
        @if(isset($actions))
            <div class="flex items-center space-x-2">
                {{ $actions }}
            </div>
        @endif
    </div>
</div>
