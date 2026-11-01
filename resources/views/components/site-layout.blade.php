@props([
    'title' => config('pos.store_name'),
    'description' => 'Siêu thị mini – hàng chính hãng, giá tốt, giao nhanh.',
    'image' => null,
    'canonical' => null,
    'type' => 'website',
    'noindex' => false,
])
@php
    $fullTitle = $title === config('pos.store_name') ? $title : $title.' | '.config('pos.store_name');
    $canonical ??= url()->current();
    $image ??= asset('og-default.png');
@endphp
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($description), 160, '') }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta name="robots" content="{{ $noindex ? 'noindex, follow' : 'index, follow, max-image-preview:large' }}">

    {{-- Open Graph / Twitter --}}
    <meta property="og:type" content="{{ $type }}">
    <meta property="og:site_name" content="{{ config('pos.store_name') }}">
    <meta property="og:title" content="{{ $fullTitle }}">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags($description), 200, '') }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $image }}">
    <meta property="og:locale" content="vi_VN">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=be-vietnam-pro:400,600,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])

    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('pos.store_name'),
            'url' => url('/'),
            'logo' => asset('favicon.ico'),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>
    {{ $head ?? '' }}
</head>
<body class="bg-stone-50 text-stone-800 antialiased">
    <header class="sticky top-0 z-20 border-b border-stone-200 bg-white/90 backdrop-blur">
        <nav class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3" aria-label="Điều hướng chính">
            <a href="{{ route('home') }}" class="text-xl font-extrabold text-amber-600">🛒 {{ config('pos.store_name') }}</a>
            <form action="{{ route('catalog.index') }}" method="get" role="search" class="hidden flex-1 sm:block sm:max-w-md">
                <label for="q" class="sr-only">Tìm sản phẩm</label>
                <input id="q" name="q" value="{{ request('q') }}" placeholder="Tìm sản phẩm..."
                       class="w-full rounded-full border border-stone-300 px-4 py-2 text-sm focus:border-amber-500 focus:outline-none">
            </form>
            <div class="flex items-center gap-4 text-sm font-semibold">
                <a href="{{ route('catalog.index') }}" class="hover:text-amber-600">Sản phẩm</a>
                <a href="{{ url('/admin') }}" rel="nofollow" class="rounded-full bg-amber-500 px-4 py-2 text-white hover:bg-amber-600">Đăng nhập</a>
            </div>
        </nav>
    </header>

    <main class="mx-auto max-w-6xl px-4 py-8">
        {{ $slot }}
    </main>

    <footer class="mt-16 border-t border-stone-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-8 text-sm text-stone-500">
            © {{ date('Y') }} {{ config('pos.store_name') }}. Dự án demo hệ thống POS – Laravel &amp; Filament.
        </div>
    </footer>
</body>
</html>
