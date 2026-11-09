<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function sitemap(): Response
    {
        $urls = collect([
            ['loc' => route('home'), 'priority' => '1.0'],
            ['loc' => route('shop'), 'priority' => '0.9'],
            ['loc' => route('contact'), 'priority' => '0.4'],
            ['loc' => route('shipping'), 'priority' => '0.4'],
            ['loc' => route('privacy'), 'priority' => '0.2'],
        ]);

        Category::query()->active()->each(fn (Category $c) => $urls->push(['loc' => route('category', $c), 'lastmod' => $c->updated_at?->toAtomString(), 'priority' => '0.8']));
        Product::query()->active()->select(['id', 'slug', 'updated_at'])->each(fn (Product $p) => $urls->push(['loc' => route('product', $p), 'lastmod' => $p->updated_at?->toAtomString(), 'priority' => '0.7']));

        return response()->view('sitemap', ['urls' => $urls], 200, ['Content-Type' => 'application/xml']);
    }

    public function robots(): Response
    {
        $lines = app()->isProduction()
            ? ['User-agent: *', 'Disallow: /admin', 'Disallow: /account', 'Disallow: /checkout', 'Disallow: /cart', 'Disallow: /orders', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain']);
    }
}
