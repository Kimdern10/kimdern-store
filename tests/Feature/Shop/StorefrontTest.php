<?php

use App\Models\Category;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use Livewire\Livewire;

it('shows the main shop pages', function (string $route) {
    simpleProduct(['is_featured' => true]);

    $this->get(route($route))->assertOk();
})->with(['home', 'shop', 'cart', 'contact', 'shipping', 'privacy', 'orders.track']);

it('shows a product with its details, price in naira and SEO tags', function () {
    $product = simpleProduct(['name' => 'Glow Serum', 'price' => 1850000, 'short_description' => 'Brightens skin.']);

    $this->get(route('product', $product))
        ->assertOk()
        ->assertSee('Glow Serum')
        ->assertSee('₦18,500')
        ->assertSee('<meta property="og:title" content="Glow Serum | Kimdern Store"', false)
        ->assertSee('"@type":"Product"', false);
});

it('hides products and categories the owner switched off', function () {
    $product = simpleProduct(['is_active' => false]);
    $category = Category::factory()->create(['is_active' => false]);

    $this->get(route('product', $product))->assertNotFound();
    $this->get(route('category', $category))->assertNotFound();
});

describe('catalogue filters', function () {
    beforeEach(function () {
        $this->skincare = Category::factory()->create(['name' => 'Skincare', 'slug' => 'skincare']);
        $this->shoes = Category::factory()->create(['name' => 'Shoes', 'slug' => 'shoes']);

        $this->serum = Product::factory()->for($this->skincare)->create(['name' => 'Vitamin C Serum', 'price' => 1500000, 'stock' => 3]);
        $this->cream = Product::factory()->for($this->skincare)->create(['name' => 'Night Cream', 'price' => 900000, 'stock' => 0, 'compare_at_price' => 1200000]);
        $this->sneaker = Product::factory()->for($this->shoes)->create(['name' => 'Leather Sneaker', 'price' => 4000000, 'stock' => 0]);
        ProductVariant::factory()->for($this->sneaker)->create(['size' => '42', 'colour' => 'Tan', 'stock' => 2]);
        ProductVariant::factory()->for($this->sneaker)->create(['size' => '44', 'colour' => 'Tan', 'stock' => 0]);
    });

    it('filters by category', function () {
        Livewire::test('pages::shop.catalog')->set('selectedCategories', ['shoes'])
            ->assertSee('Leather Sneaker')->assertDontSee('Vitamin C Serum');
    });

    it('lists a category on its own page', function () {
        $this->get(route('category', $this->skincare))->assertSee('Vitamin C Serum')->assertDontSee('Leather Sneaker');
    });

    it('filters by price in naira', function () {
        Livewire::test('pages::shop.catalog')->set('min', 10000)->set('max', 20000)
            ->assertSee('Vitamin C Serum')->assertDontSee('Night Cream')->assertDontSee('Leather Sneaker');
    });

    it('filters by size, only counting sizes in stock', function () {
        Livewire::test('pages::shop.catalog')->set('sizes', ['42'])->assertSee('Leather Sneaker')->assertDontSee('Vitamin C Serum');
        Livewire::test('pages::shop.catalog')->set('sizes', ['44'])->assertDontSee('Leather Sneaker');
    });

    it('filters by colour', function () {
        Livewire::test('pages::shop.catalog')->set('colours', ['Tan'])->assertSee('Leather Sneaker')->assertDontSee('Night Cream');
    });

    it('shows only items on sale or in stock when asked', function () {
        Livewire::test('pages::shop.catalog')->set('sale', true)->assertSee('Night Cream')->assertDontSee('Vitamin C Serum');
        Livewire::test('pages::shop.catalog')->set('inStock', true)->assertSee('Leather Sneaker')->assertSee('Vitamin C Serum')->assertDontSee('Night Cream');
    });

    it('searches as you type', function () {
        Livewire::test('pages::shop.catalog')->set('q', 'serum')->assertSee('Vitamin C Serum')->assertDontSee('Leather Sneaker');
        Livewire::test('search-box')->set('query', 'sneak')->assertSee('Leather Sneaker')->assertDontSee('Night Cream');
    });

    it('sorts by price', function () {
        Livewire::test('pages::shop.catalog')->set('sort', 'price-low')
            ->assertSeeInOrder(['Night Cream', 'Vitamin C Serum', 'Leather Sneaker']);
    });
});

describe('reviews', function () {
    it('lets a logged-in customer send a review that waits for approval', function () {
        $user = User::factory()->create();
        $product = simpleProduct();

        Livewire::actingAs($user)->test('pages::shop.product', ['product' => $product])
            ->set('rating', 4)
            ->set('reviewBody', 'Lovely texture and smells great.')
            ->call('submitReview')
            ->assertHasNoErrors()
            ->assertSet('reviewSent', true);

        $review = Review::query()->sole();
        expect($review->is_approved)->toBeFalse()
            ->and($review->is_verified_buyer)->toBeFalse()
            ->and($product->fresh()->reviews_count)->toBe(0);
    });

    it('marks the reviewer as a verified buyer when they paid for the product', function () {
        $user = User::factory()->create();
        $product = simpleProduct();
        Order::factory()->paid()->for($user)->create()->items()->create(['product_id' => $product->id, 'name' => $product->name, 'price' => 100, 'quantity' => 1, 'line_total' => 100]);

        Livewire::actingAs($user)->test('pages::shop.product', ['product' => $product])
            ->set('reviewBody', 'Exactly as described.')
            ->call('submitReview');

        expect(Review::query()->sole()->is_verified_buyer)->toBeTrue();
    });

    it('does not let guests post reviews', function () {
        Livewire::test('pages::shop.product', ['product' => simpleProduct()])
            ->set('reviewBody', 'Spam spam spam spam')
            ->call('submitReview')
            ->assertForbidden();
    });
});

it('lists products and categories in the sitemap and keeps robots away from test sites', function () {
    $product = simpleProduct();

    $this->get(route('sitemap'))->assertOk()->assertHeader('Content-Type', 'application/xml')->assertSee(route('product', $product));
    $this->get(route('robots'))->assertOk()->assertSee('Disallow: /');
});

it('saves newsletter sign-ups once', function () {
    Livewire::test('newsletter-form')->set('email', 'Ada@Example.com')->call('subscribe')->assertSet('subscribed', true);
    Livewire::test('newsletter-form')->set('email', 'ada@example.com')->call('subscribe');

    expect(NewsletterSubscriber::query()->pluck('email')->all())->toBe(['ada@example.com']);
});
