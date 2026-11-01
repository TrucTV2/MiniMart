<x-site-layout title="{{ config('pos.store_name') }}" description="Siêu thị mini {{ config('pos.store_name') }}: thực phẩm, đồ uống, hóa mỹ phẩm chính hãng với giá tốt mỗi ngày.">
    <section class="rounded-3xl bg-gradient-to-br from-amber-400 to-orange-500 px-6 py-14 text-white sm:px-12">
        <h1 class="max-w-2xl text-3xl font-extrabold leading-tight sm:text-5xl">Mua sắm tiện lợi, giá tốt mỗi ngày</h1>
        <p class="mt-4 max-w-xl text-lg text-amber-50">Hàng nghìn sản phẩm chính hãng, cập nhật tồn kho theo thời gian thực tại các chi nhánh.</p>
        <a href="{{ route('catalog.index') }}" class="mt-8 inline-block rounded-full bg-white px-6 py-3 font-bold text-orange-600 hover:bg-amber-50">Xem sản phẩm →</a>
    </section>

    @if ($categories->isNotEmpty())
        <section class="mt-12" aria-labelledby="cat-heading">
            <h2 id="cat-heading" class="text-2xl font-extrabold">Danh mục</h2>
            <ul class="mt-4 flex flex-wrap gap-3">
                @foreach ($categories as $cat)
                    <li>
                        <a href="{{ route('catalog.category', $cat) }}" class="inline-block rounded-full border border-stone-300 bg-white px-4 py-2 text-sm font-semibold hover:border-amber-500 hover:text-amber-600">
                            {{ $cat->name }} <span class="text-stone-400">({{ $cat->products_count }})</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="mt-12" aria-labelledby="new-heading">
        <h2 id="new-heading" class="text-2xl font-extrabold">Sản phẩm mới</h2>
        <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
            @foreach ($featured as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
    </section>
</x-site-layout>
