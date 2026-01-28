<!-- Empty State -->
@if(isset($empty_state))
    <div class="text-center py-16">
        @if(isset($empty_state['icon']))
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $empty_state['icon'] }}" />
            </svg>
        @endif
        
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $empty_state['title'] ?? 'No items found' }}</h3>
        <p class="mt-1 text-sm text-gray-500">
            {{ $empty_state['message'] ?? 'No items available at the moment.' }}
            
            @if(isset($empty_state['action_text']) && isset($empty_state['action_url']))
                <a href="{{ $empty_state['action_url'] }}" class="text-primary hover:text-primary-800 font-medium">
                    {{ $empty_state['action_text'] }}
                </a>
            @endif
        </p>
    </div>
@endif
