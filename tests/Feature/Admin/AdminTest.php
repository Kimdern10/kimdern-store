<?php

use App\Enums\OrderStatus;
use App\Mail\OrderStatusChanged;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('keeps customers and guests out of the admin', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs(User::factory()->create())->get(route('admin.orders'))->assertForbidden();
});

it('blocks admin button clicks once the user is no longer an admin', function () {
    $product = simpleProduct();
    $page = Livewire::actingAs($this->admin)->test('pages::admin.products');

    $this->admin->forceFill(['is_admin' => false])->save();

    $page->call('delete', $product->id)->assertForbidden();
    expect($product->fresh())->not->toBeNull();
});

it('opens every admin page', function (string $route) {
    simpleProduct();
    Order::factory()->paid()->create();

    $this->actingAs($this->admin)->get(route($route))->assertOk();
})->with(['admin.dashboard', 'admin.orders', 'admin.products', 'admin.products.create', 'admin.categories', 'admin.coupons', 'admin.zones', 'admin.customers', 'admin.reviews', 'admin.banners', 'admin.settings']);

it('shows this month\'s paid sales and low stock on the dashboard', function () {
    Order::factory()->paid()->create(['total' => 4500000]);
    Order::factory()->create(['total' => 9900000]); // not paid
    simpleProduct(['name' => 'Almost Gone Balm', 'stock' => 2]);

    $dashboard = Livewire::actingAs($this->admin)->test('pages::admin.dashboard')->assertSee('Almost Gone Balm');

    expect($dashboard->instance()->stats['revenue_month'])->toBe(4500000)
        ->and($dashboard->instance()->stats['awaiting_payment'])->toBe(1);
});

describe('products', function () {
    it('creates a product with photos turned into WebP, and sizes with their own stock', function () {
        Storage::fake('public');
        $category = Category::factory()->create();

        Livewire::actingAs($this->admin)->test('pages::admin.product-form')
            ->set('name', 'Silk Wrap Dress')
            ->set('category_id', $category->id)
            ->set('price', '25000')
            ->set('compare_at_price', '30000')
            ->set('photos', [UploadedFile::fake()->image('dress.jpg', 2000, 2500)])
            ->call('addVariant')->set('variants.0.size', 'M')->set('variants.0.stock', 4)
            ->call('addVariant')->set('variants.1.size', 'L')->set('variants.1.stock', 2)->set('variants.1.price', '27000')
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::query()->sole();
        expect($product->slug)->toBe('silk-wrap-dress')
            ->and($product->price)->toBe(2500000)
            ->and($product->compare_at_price)->toBe(3000000)
            ->and($product->variants)->toHaveCount(2)
            ->and($product->variants->firstWhere('size', 'L')->price)->toBe(2700000)
            ->and($product->totalStock())->toBe(6);

        $path = $product->images->sole()->path;
        expect($path)->toEndWith('.webp');
        Storage::disk('public')->assertExists([$path, str_replace('.webp', '-sm.webp', $path)]);
        expect(getimagesizefromstring(Storage::disk('public')->get($path))[0])->toBe(1600);
    });

    it('validates prices and options', function () {
        Livewire::actingAs($this->admin)->test('pages::admin.product-form')
            ->set('name', 'Thing')
            ->set('category_id', Category::factory()->create()->id)
            ->set('price', '20000')
            ->set('compare_at_price', '15000')
            ->call('save')
            ->assertHasErrors('compare_at_price');

        Livewire::actingAs($this->admin)->test('pages::admin.product-form')
            ->set('name', 'Thing')
            ->set('category_id', Category::factory()->create()->id)
            ->set('price', '20000')
            ->call('addVariant')
            ->call('save')
            ->assertHasErrors('variants.0.size');

        expect(Product::query()->count())->toBe(0);
    });

    it('edits a product and removes deleted options', function () {
        $product = sizedProduct(['M' => 1, 'L' => 1]);

        Livewire::actingAs($this->admin)->test('pages::admin.product-form', ['product' => $product])
            ->assertSet('price', '20000')
            ->set('price', '18000')
            ->call('removeVariant', 1)
            ->call('save')
            ->assertHasNoErrors();

        expect($product->fresh()->price)->toBe(1800000)
            ->and($product->variants()->pluck('size')->all())->toBe(['M']);
    });

    it('hides a product from the shop', function () {
        $product = simpleProduct();

        Livewire::actingAs($this->admin)->test('pages::admin.products')->call('toggleActive', $product->id);

        expect($product->fresh()->is_active)->toBeFalse();
    });
});

describe('orders', function () {
    beforeEach(fn () => Mail::fake());

    it('moves an order along and emails the customer each time', function () {
        $order = Order::factory()->paid()->create();

        Livewire::actingAs($this->admin)->test('pages::admin.order', ['order' => $order])
            ->assertSet('status', 'processing')
            ->call('updateStatus')
            ->set('status', 'shipped')->set('trackingNumber', 'GIG-123')->set('note', 'Rider will call.')
            ->call('updateStatus')
            ->assertHasNoErrors();

        $order->refresh();
        expect($order->status)->toBe(OrderStatus::Shipped)
            ->and($order->tracking_number)->toBe('GIG-123')
            ->and($order->history()->pluck('status')->map->value->all())->toBe(['processing', 'shipped']);

        Mail::assertSent(OrderStatusChanged::class, 2);
        Mail::assertSent(OrderStatusChanged::class, fn ($mail) => $mail->hasTo($order->email) && $mail->note === 'Rider will call.');
    });

    it('marks a bank transfer order as paid, with the receipt attached to the email', function () {
        $order = Order::factory()->create(['payment_method' => 'bank_transfer']);

        Livewire::actingAs($this->admin)->test('pages::admin.order', ['order' => $order])->set('status', 'paid')->call('updateStatus');

        expect($order->fresh()->paid_at)->not->toBeNull();
        Mail::assertSent(OrderStatusChanged::class, fn ($mail) => count($mail->attachments()) === 1);
    });

    it('puts items back in stock when an order is cancelled', function () {
        $product = simpleProduct(['stock' => 3]);
        $sized = sizedProduct(['M' => 1]);
        $order = Order::factory()->create();
        $order->items()->create(['product_id' => $product->id, 'name' => 'A', 'price' => 1, 'quantity' => 2, 'line_total' => 2]);
        $order->items()->create(['product_id' => $sized->id, 'product_variant_id' => $sized->variants->first()->id, 'name' => 'B', 'price' => 1, 'quantity' => 1, 'line_total' => 1]);

        Livewire::actingAs($this->admin)->test('pages::admin.order', ['order' => $order])->set('status', 'cancelled')->call('updateStatus');

        expect($product->fresh()->stock)->toBe(5)
            ->and($sized->variants->first()->fresh()->stock)->toBe(2);
    });

    it('will not change a cancelled order or cancel a delivered one', function () {
        $cancelled = Order::factory()->create(['status' => OrderStatus::Cancelled]);
        $delivered = Order::factory()->create(['status' => OrderStatus::Delivered]);

        Livewire::actingAs($this->admin)->test('pages::admin.order', ['order' => $cancelled])->set('status', 'paid')->call('updateStatus')->assertHasErrors('status');
        Livewire::actingAs($this->admin)->test('pages::admin.order', ['order' => $delivered])->set('status', 'cancelled')->call('updateStatus')->assertHasErrors('status');

        Mail::assertNothingSent();
    });
});

it('creates coupons with naira amounts saved in kobo', function () {
    Livewire::actingAs($this->admin)->test('pages::admin.coupons')
        ->set('code', 'sallah20')->set('type', 'fixed')->set('value', '2000')->set('min_subtotal', '15000')
        ->call('save')
        ->assertHasNoErrors();

    $coupon = Coupon::query()->sole();
    expect($coupon->code)->toBe('SALLAH20')->and($coupon->value)->toBe(200000)->and($coupon->min_subtotal)->toBe(1500000);
});

it('creates a delivery zone that checkout then uses', function () {
    Livewire::actingAs($this->admin)->test('pages::admin.zones')
        ->set('name', 'South West')->set('states', ['Oyo', 'Ogun'])->set('fee', '3500')->set('eta', '2–3 days')
        ->call('save')
        ->assertHasNoErrors();

    expect(DeliveryZone::forState('Oyo')->fee)->toBe(350000);
});

it('approves a review and updates the product rating', function () {
    $product = simpleProduct();
    Review::factory()->for($product)->create(['rating' => 4]);
    $pending = Review::factory()->pending()->for($product)->create(['rating' => 2]);

    expect($product->fresh()->rating)->toBe(4.0);

    Livewire::actingAs($this->admin)->test('pages::admin.reviews')->call('approve', $pending->id);

    expect($product->fresh())->rating->toBe(3.0)->reviews_count->toBe(2);
});

it('saves site settings and a WebP logo', function () {
    Storage::fake('public');

    Livewire::actingAs($this->admin)->test('pages::admin.settings')
        ->set('values.store_name', 'Kimdern Beauty')
        ->set('values.bank_account_number', '0123456789')
        ->set('logo', UploadedFile::fake()->image('logo.png', 800, 200))
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('store_name'))->toBe('Kimdern Beauty')
        ->and(Setting::get('logo'))->toEndWith('.webp');
    $this->get(route('home'))->assertSee('Kimdern Beauty');
});

it('creates the owner\'s admin login from the command line', function () {
    $this->artisan('app:create-admin', ['--name' => 'Kim Owner', '--email' => 'Owner@Kimdern.test', '--password' => 'a-strong-pass-123'])
        ->assertSuccessful();

    $owner = User::query()->where('email', 'owner@kimdern.test')->sole();
    expect($owner->is_admin)->toBeTrue()
        ->and($owner->hasVerifiedEmail())->toBeTrue()
        ->and(Hash::check('a-strong-pass-123', $owner->password))->toBeTrue();
});
