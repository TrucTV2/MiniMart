<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function home(): View
    {
        $data = Cache::remember('home:v1', now()->addMinutes(10), fn () => [
            'featured' => Product::published()->with('category')->latest()->take(8)->get(),
            'categories' => Category::where('is_visible', true)->withCount(['products' => fn ($q) => $q->published()])->get(),
        ]);

        return view('site.home', $data);
    }

    public function index(Request $request): View
    {
        $products = Product::published()
            ->with('category')
            ->when($request->string('q')->trim()->value(), fn ($q, $s) => $q->where('name', 'like', "%{$s}%"))
            ->when($request->input('sort') === 'price_asc', fn ($q) => $q->orderBy('price'))
            ->when($request->input('sort') === 'price_desc', fn ($q) => $q->orderByDesc('price'))
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('site.catalog', [
            'products' => $products,
            'categories' => $this->categories(),
            'category' => null,
            // Trang tìm kiếm không nên index để tránh nội dung trùng lặp.
            'noindex' => $request->hasAny(['q', 'sort']),
        ]);
    }

    public function category(Category $category): View
    {
        abort_unless($category->is_visible, 404);

        return view('site.catalog', [
            'products' => $category->products()->published()->with('category')->latest('id')->paginate(12),
            'categories' => $this->categories(),
            'category' => $category,
            'noindex' => false,
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active && $product->is_published, 404);

        $product->load('category');

        $related = Product::published()
            ->with('category')
            ->where('category_id', $product->category_id)
            ->whereKeyNot($product->id)
            ->take(4)->get();

        return view('site.product', compact('product', 'related'));
    }

    private function categories()
    {
        return Cache::remember('site:categories', now()->addMinutes(10), fn () => Category::where('is_visible', true)->orderBy('name')->get());
    }
}
