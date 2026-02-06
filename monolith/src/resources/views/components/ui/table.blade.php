@props([
    'headers' => [],
    'emptyMessage' => 'No records found',
    'emptyIcon' => 'fas fa-inbox',
    'emptyAction' => null,
])

<div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    @foreach($headers as $header)
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            {{ $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                {{ $slot }}
            </tbody>
        </table>
    </div>
    
    @if(isset($empty) && $empty)
        <div class="px-6 py-12 text-center">
            <i class="{{ $emptyIcon }} mx-auto h-12 w-12 text-gray-400 mb-4"></i>
            <p class="text-gray-500 text-lg font-medium">{{ $emptyMessage }}</p>
            @if($emptyAction)
                <div class="mt-4">
                    {{ $emptyAction }}
                </div>
            @endif
        </div>
    @endif
    
    @if(isset($pagination))
        <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
            {{ $pagination }}
        </div>
    @endif
</div>
