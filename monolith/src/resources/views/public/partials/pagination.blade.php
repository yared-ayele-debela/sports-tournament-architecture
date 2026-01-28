<!-- Pagination -->
@if(isset($data) && method_exists($data, 'links'))
    <div class="mt-12">
        {{ $data->links() }}
    </div>
@endif
