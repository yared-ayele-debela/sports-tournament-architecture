<!-- Page Header -->
<section class="bg-blue-500 text-white py-12">
    <div class="container mx-auto px-4">
        <div class="text-center">
            <h1 class="text-4xl font-bold mb-4">{{ $title ?? 'Page Title' }}</h1>
            @if(isset($subtitle))
                <p class="text-xl text-blue-100">{{ $subtitle }}</p>
            @endif
        </div>
    </div>
</section>
