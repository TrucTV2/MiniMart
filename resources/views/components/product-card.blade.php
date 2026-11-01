@props(['product'])
<article class="group overflow-hidden rounded-2xl border border-stone-200 bg-white transition hover:-translate-y-1 hover:shadow-lg">
    <a href="{{ route('catalog.show', $product) }}" class="block">
        <div class="flex aspect-square items-center justify-center bg-amber-50">
            @if ($product->imageUrl())
                <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="lazy" width="400" height="400"
                     class="h-full w-full object-cover">
            @else
                <span class="text-5xl font-extrabold text-amber-300" aria-hidden="true">{{ mb_substr($product->name, 0, 1) }}</span>
            @endif
        </div>
        <div class="p-4">
            @if ($product->category)
                <p class="text-xs font-semibold uppercase tracking-wide text-stone-400">{{ $product->category->name }}</p>
            @endif
            <h3 class="mt-1 line-clamp-2 font-semibold group-hover:text-amber-600">{{ $product->name }}</h3>
            <p class="mt-2 text-lg font-extrabold text-amber-600">{{ number_format($product->price, 0, ',', '.') }}đ</p>
        </div>
    </a>
</article>
