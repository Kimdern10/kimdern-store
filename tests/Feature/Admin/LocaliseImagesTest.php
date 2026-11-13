<?php

use App\Models\Banner;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

it('saves photos that come from other websites on this site as WebP', function () {
    Storage::fake('public');

    $image = imagecreatetruecolor(1800, 1200);
    ob_start();
    imagejpeg($image);
    $jpeg = (string) ob_get_clean();

    Http::fake(['images.unsplash.com/*' => Http::response($jpeg, 200, ['Content-Type' => 'image/jpeg'])]);

    $product = simpleProduct();
    $photo = $product->images()->create(['path' => 'https://images.unsplash.com/photo-123?w=1200&q=80&auto=format&fit=crop']);
    $local = $product->images()->create(['path' => 'products/already-here.webp']);
    $banner = Banner::query()->create(['title' => 'Sale', 'image' => 'https://images.unsplash.com/photo-456?w=1800&q=80&auto=format&fit=crop']);

    $this->artisan('app:localise-images')->assertSuccessful();

    $path = $photo->fresh()->path;
    expect($path)->toStartWith('products/')->toEndWith('.webp')
        ->and($local->fresh()->path)->toBe('products/already-here.webp')
        ->and($banner->fresh()->image)->toStartWith('banners/');
    Storage::disk('public')->assertExists([$path, str_replace('.webp', '-sm.webp', $path)]);

    // Asked Unsplash for a plain JPEG that PHP can read.
    Http::assertSent(fn ($request) => str_contains($request->url(), 'fm=jpg') && ! str_contains($request->url(), 'auto=format'));
    expect(ProductImage::query()->where('path', 'like', 'http%')->count())->toBe(0);
});
