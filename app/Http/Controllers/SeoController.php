<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class SeoController extends Controller
{
    public function sitemap(): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), function () {
            $sitemap = Sitemap::create()
                ->add(Url::create(route('home'))->setPriority(1.0)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY))
                ->add(Url::create(route('catalog.index'))->setPriority(0.9)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY));

            Category::where('is_visible', true)->each(fn (Category $c) => $sitemap->add(
                Url::create(route('catalog.category', $c))->setLastModificationDate($c->updated_at)->setPriority(0.8)
            ));

            Product::published()->select(['id', 'slug', 'updated_at'])->chunk(500, function ($products) use ($sitemap) {
                foreach ($products as $p) {
                    $sitemap->add(Url::create(route('catalog.show', $p))->setLastModificationDate($p->updated_at)->setPriority(0.7));
                }
            });

            return $sitemap->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function robots(): Response
    {
        $lines = app()->isProduction()
            ? ['User-agent: *', 'Disallow: /admin', 'Disallow: /orders', 'Allow: /', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /']; // không cho index môi trường staging/dev

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain']);
    }
}
