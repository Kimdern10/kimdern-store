<?php

use App\Models\Coupon;
use App\Support\Cart;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function cart(): Cart
{
    return app(Cart::class);
}

it('adds a simple product from a product card and opens the cart', function () {
    $product = simpleProduct(['price' => 1250000]);

    Livewire::test('pages::shop.home')
        ->call('addToCart', $product->id)
        ->assertDispatched('cart-updated', open: true);

    expect(cart()->count())->toBe(1)
        ->and(cart()->subtotal())->toBe(1250000);
});

it('asks for a size before adding a product that has sizes', function () {
    $product = sizedProduct();

    Livewire::test('pages::shop.product', ['product' => $product])
        ->call('addSelectedToCart')
        ->assertNotDispatched('cart-updated');

    expect(cart()->isEmpty())->toBeTrue();
});

it('adds the chosen size at its own price', function () {
    $product = sizedProduct(['M' => 5]);
    $product->variants->first()->update(['price' => 2500000]);

    Livewire::test('pages::shop.product', ['product' => $product->fresh()])
        ->set('size', 'M')
        ->set('quantity', 2)
        ->call('addSelectedToCart')
        ->assertDispatched('cart-updated');

    $line = cart()->lines()->first();
    expect($line->variant->size)->toBe('M')
        ->and($line->quantity)->toBe(2)
        ->and(cart()->subtotal())->toBe(5000000);
});

it('will not add more than is in stock', function () {
    $product = simpleProduct(['stock' => 2]);

    cart()->add($product, null, 2);

    expect(fn () => cart()->add($product))->toThrow(ValidationException::class, 'Only 2 left in stock.');
    expect(cart()->count())->toBe(2);
});

it('will not add a sold-out size', function () {
    $product = sizedProduct(['M' => 5, 'L' => 0]);

    expect(fn () => cart()->add($product, $product->variants->firstWhere('size', 'L')))->toThrow(ValidationException::class, 'out of stock');
});

it('drops lines whose product was hidden and caps quantities at the stock left', function () {
    $hidden = simpleProduct();
    $shrinking = simpleProduct(['stock' => 5]);
    cart()->add($hidden);
    cart()->add($shrinking, null, 4);

    $hidden->update(['is_active' => false]);
    $shrinking->update(['stock' => 1]);

    $fresh = new Cart(app('session.store'));
    expect($fresh->lines())->toHaveCount(1)
        ->and($fresh->lines()->first()->quantity)->toBe(1);
});

it('changes quantities and removes lines from the cart drawer', function () {
    $product = simpleProduct();
    cart()->add($product);
    $key = Cart::key($product->id);

    Livewire::test('cart-drawer')->call('changeQuantity', $key, 3);
    expect(cart()->count())->toBe(3);

    Livewire::test('cart-drawer')->call('removeLine', $key);
    expect((new Cart(app('session.store')))->isEmpty())->toBeTrue();
});

describe('coupons', function () {
    it('takes a percentage off the subtotal', function () {
        cart()->add(simpleProduct(['price' => 2000000]));
        Coupon::factory()->create(['code' => 'WELCOME10', 'value' => 10]);

        Livewire::test('pages::shop.cart')
            ->set('couponCode', 'welcome10')
            ->call('applyCoupon')
            ->assertHasNoErrors();

        expect(cart()->discount())->toBe(200000);
    });

    it('takes a naira amount off, never more than the subtotal', function () {
        cart()->add(simpleProduct(['price' => 300000]));
        Coupon::factory()->fixed(500000)->create(['code' => 'BIG']);

        expect(cart()->applyCoupon('BIG'))->toBeNull()
            ->and(cart()->discount())->toBe(300000);
    });

    it('refuses a code below its minimum spend', function () {
        cart()->add(simpleProduct(['price' => 1000000]));
        Coupon::factory()->fixed(500000)->create(['code' => 'GLOW5K', 'min_subtotal' => 4000000]);

        Livewire::test('pages::shop.cart')
            ->set('couponCode', 'GLOW5K')
            ->call('applyCoupon')
            ->assertHasErrors('couponCode');

        expect(cart()->discount())->toBe(0);
    });

    it('refuses expired, used-up, inactive and unknown codes', function (array $state, string $message) {
        cart()->add(simpleProduct());
        Coupon::factory()->create(['code' => 'TRYME'] + $state);

        expect(cart()->applyCoupon('TRYME'))->toBe($message);
    })->with([
        'expired' => [['expires_at' => now()->subDay()], 'This code has expired.'],
        'used up' => [['max_uses' => 3, 'used_count' => 3], 'This code has been fully used.'],
        'inactive' => [['is_active' => false], 'This code is not active.'],
        'not started' => [['starts_at' => now()->addDay()], 'This code is not active yet.'],
    ]);

    it('says when a code does not exist', function () {
        cart()->add(simpleProduct());

        expect(cart()->applyCoupon('NOPE'))->toBe('That code is not valid.');
    });
});

describe('delivery fees', function () {
    it('charges the fee of the zone that covers the state', function () {
        lagosZone(fee: 250000);
        cart()->add(simpleProduct(['price' => 1000000]));

        $totals = cart()->totals('Lagos');

        expect($totals['delivery_fee'])->toBe(250000)
            ->and($totals['total'])->toBe(1250000);
    });

    it('delivers free above the zone limit, after the discount', function () {
        lagosZone(fee: 250000, freeOver: 5000000);
        cart()->add(simpleProduct(['price' => 5000000]));

        expect(cart()->totals('Lagos')['delivery_fee'])->toBe(0);

        Coupon::factory()->create(['code' => 'TEN', 'value' => 10]);
        cart()->applyCoupon('TEN');

        expect(cart()->totals('Lagos')['delivery_fee'])->toBe(250000);
    });

    it('has no fee for a state no active zone covers', function () {
        lagosZone();
        cart()->add(simpleProduct());

        expect(cart()->totals('Kano')['zone'])->toBeNull()
            ->and(cart()->totals('Kano')['delivery_fee'])->toBeNull();
    });
});
