<?php

use App\Http\Controllers\CatalogController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

// Website công khai (chuẩn SEO)
Route::get('/', [CatalogController::class, 'home'])->name('home');
Route::get('/san-pham', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/san-pham/{product:slug}', [CatalogController::class, 'show'])->name('catalog.show');
Route::get('/danh-muc/{category:slug}', [CatalogController::class, 'category'])->name('catalog.category');

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots']);

// Hóa đơn (cần đăng nhập + quyền xem đơn)
Route::get('/orders/{order}/receipt', ReceiptController::class)
    ->middleware(['auth', 'can:view,order'])
    ->name('orders.receipt');
