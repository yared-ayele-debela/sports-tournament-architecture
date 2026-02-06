@props([
    'label',
    'required' => false,
    'error' => null,
    'help' => null,
])

<div {{ $attributes->merge(['class' => '']) }}>
    <label class="block text-sm font-medium text-gray-700 mb-2">
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    
    {{ $slot }}
    
    @if($error)
        <p class="mt-1 text-sm text-red-600">{{ $error }}</p>
    @endif
    
    @if($help)
        <p class="mt-1 text-xs text-gray-500">{{ $help }}</p>
    @endif
</div>
