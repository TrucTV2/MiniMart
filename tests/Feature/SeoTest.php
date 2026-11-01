<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_public_pages_render_with_meta_tags(): void
    {
        $product = Product::published()->firstOrFail();

        $this->get('/')->assertOk()->assertSee('<link rel="canonical"', false)->assertSee('og:title', false);
        $this->get('/san-pham')->assertOk()
            ->assertSee(Product::published()->latest('id')->first()->name)
            ->assertSee('href="'.url('/san-pham?page=2').'"', false);
        $this->get(route('catalog.category', Category::first()))->assertOk()->assertSee('BreadcrumbList', false);

        $this->get(route('catalog.show', $product))
            ->assertOk()
            ->assertSee('"@type":"Product"', false)
            ->assertSee('<meta name="robots" content="index', false)
            ->assertSee(route('catalog.show', $product), false);
    }

    public function test_unpublished_product_returns_404(): void
    {
        $product = Product::first();
        $product->update(['is_published' => false]);

        $this->get(route('catalog.show', $product))->assertNotFound();
    }

    public function test_search_pages_are_noindex(): void
    {
        $this->get('/san-pham?q=mi')->assertOk()->assertSee('noindex, follow', false);
    }

    public function test_sitemap_lists_published_products(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee(route('catalog.show', Product::published()->first()), false);
    }

    public function test_robots_and_admin_noindex(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /');
        $this->get('/admin/login')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }
}
