@php
    $metaTitle = $product->meta_title ?: $product->name;
    $metaDesc = $product->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($product->description ?? ''), 155)
        ?: $product->name.' giá '.number_format($product->price, 0, ',', '.').'đ tại '.config('pos.store_name');
@endphp
<x-site-layout :title="$metaTitle" :description="$metaDesc" :image="$product->imageUrl()" :canonical="route('catalog.show', $product)" type="product">
    <x-slot:head>
        <meta property="product:price:amount" content="{{ $product->price }}">
        <meta property="product:price:currency" content="VND">
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'Product',
                'name' => $product->name,
                'sku' => $product->sku,
                'gtin13' => $product->barcode,
                'image' => $product->imageUrl() ? [$product->imageUrl()] : null,
                'description' => $metaDesc,
                'category' => $product->category?->name,
                'brand' => ['@type' => 'Brand', 'name' => config('pos.store_name')],
                'offers' => [
                    '@type' => 'Offer',
                    'url' => route('catalog.show', $product),
                    'priceCurrency' => 'VND',
                    'price' => $product->price,
                    'availability' => 'https://schema.org/InStock',
                    'itemCondition' => 'https://schema.org/NewCondition',
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
        </script>
    </x-slot:head>

    <nav aria-label="Breadcrumb" class="mb-6 text-sm text-stone-500">
        <a href="{{ route('home') }}" class="hover:text-amber-600">Trang chủ</a> /
        <a href="{{ route('catalog.index') }}" class="hover:text-amber-600">Sản phẩm</a>
        @if ($product->category)
            / <a href="{{ route('catalog.category', $product->category) }}" class="hover:text-amber-600">{{ $product->category->name }}</a>
        @endif
    </nav>

    <article class="grid gap-10 rounded-3xl bg-white p-6 md:grid-cols-2 md:p-10">
        <div class="flex aspect-square items-center justify-center overflow-hidden rounded-2xl bg-amber-50">
            @if ($product->imageUrl())
                <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" width="600" height="600" fetchpriority="high" class="h-full w-full object-cover">
            @else
                <span class="text-8xl font-extrabold text-amber-300" aria-hidden="true">{{ mb_substr($product->name, 0, 1) }}</span>
            @endif
        </div>
        <div>
            <h1 class="text-3xl font-extrabold leading-tight">{{ $product->name }}</h1>
            <p class="mt-2 text-sm text-stone-500">Mã: {{ $product->sku }} · Đơn vị: {{ $product->unit }}</p>
            <p class="mt-6 text-4xl font-extrabold text-amber-600">{{ number_format($product->price, 0, ',', '.') }}đ</p>
            <p class="mt-2 inline-block rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-700">Còn hàng tại cửa hàng</p>
            @if ($product->description)
                <div class="prose mt-8 max-w-none text-stone-700">{!! str($product->description)->sanitizeHtml() !!}</div>
            @endif
        </div>
    </article>

    @if ($related->isNotEmpty())
        <section class="mt-12" aria-labelledby="related-heading">
            <h2 id="related-heading" class="text-2xl font-extrabold">Sản phẩm liên quan</h2>
            <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
                @foreach ($related as $item)
                    <x-product-card :product="$item" />
                @endforeach
            </div>
        </section>
    @endif
</x-site-layout>
