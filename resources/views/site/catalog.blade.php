@php
    $title = $category ? $category->name : (request('q') ? 'Tìm kiếm: '.request('q') : 'Tất cả sản phẩm');
    $desc = $category?->description ?: 'Danh sách '.mb_strtolower($title).' tại '.config('pos.store_name').' – giá tốt, hàng chính hãng.';
    $canonical = $category ? route('catalog.category', $category) : route('catalog.index');
    if ($products->currentPage() > 1) {
        $canonical .= '?page='.$products->currentPage();
    }
@endphp
<x-site-layout :title="$title" :description="$desc" :canonical="$canonical" :noindex="$noindex">
    <x-slot:head>
        @if ($products->previousPageUrl())<link rel="prev" href="{{ $products->previousPageUrl() }}">@endif
        @if ($products->nextPageUrl())<link rel="next" href="{{ $products->nextPageUrl() }}">@endif
        <script type="application/ld+json">
            {!! json_encode([
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => array_values(array_filter([
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Trang chủ', 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Sản phẩm', 'item' => route('catalog.index')],
                    $category ? ['@type' => 'ListItem', 'position' => 3, 'name' => $category->name, 'item' => route('catalog.category', $category)] : null,
                ])),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
        </script>
    </x-slot:head>

    <nav aria-label="Breadcrumb" class="mb-4 text-sm text-stone-500">
        <ol class="flex flex-wrap gap-1">
            <li><a href="{{ route('home') }}" class="hover:text-amber-600">Trang chủ</a> /</li>
            <li><a href="{{ route('catalog.index') }}" class="hover:text-amber-600">Sản phẩm</a>@if ($category) /@endif</li>
            @if ($category)<li aria-current="page" class="text-stone-700">{{ $category->name }}</li>@endif
        </ol>
    </nav>

    <div class="flex flex-col gap-8 lg:flex-row">
        <aside class="lg:w-56 lg:shrink-0">
            <h2 class="mb-3 font-bold">Danh mục</h2>
            <ul class="flex flex-wrap gap-2 lg:flex-col">
                <li><a href="{{ route('catalog.index') }}" class="{{ ! $category ? 'font-bold text-amber-600' : 'hover:text-amber-600' }}">Tất cả</a></li>
                @foreach ($categories as $cat)
                    <li><a href="{{ route('catalog.category', $cat) }}" class="{{ $category?->id === $cat->id ? 'font-bold text-amber-600' : 'hover:text-amber-600' }}">{{ $cat->name }}</a></li>
                @endforeach
            </ul>
        </aside>

        <section class="flex-1">
            <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h1 class="text-3xl font-extrabold">{{ $title }}</h1>
                    <p class="text-sm text-stone-500">{{ $products->total() }} sản phẩm</p>
                </div>
                <form method="get" class="text-sm">
                    @if (request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                    <label for="sort" class="sr-only">Sắp xếp</label>
                    <select id="sort" name="sort" onchange="this.form.submit()" class="rounded-lg border border-stone-300 bg-white px-3 py-2">
                        <option value="">Mới nhất</option>
                        <option value="price_asc" @selected(request('sort') === 'price_asc')>Giá thấp → cao</option>
                        <option value="price_desc" @selected(request('sort') === 'price_desc')>Giá cao → thấp</option>
                    </select>
                </form>
            </div>

            @if ($products->isEmpty())
                <p class="rounded-2xl bg-white p-10 text-center text-stone-500">Không có sản phẩm phù hợp.</p>
            @else
                <div class="grid grid-cols-2 gap-4 md:grid-cols-3">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
                <div class="mt-8">{{ $products->links('pagination::tailwind') }}</div>
            @endif
        </section>
    </div>
</x-site-layout>
