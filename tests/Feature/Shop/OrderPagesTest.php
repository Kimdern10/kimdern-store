<?php

use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;

it('shows a guest their order only through the signed link', function () {
    $order = Order::factory()->create();

    $this->get(route('orders.show', $order))->assertForbidden();
    $this->get($order->trackingUrl())->assertOk()->assertSee($order->number);
});

it('shows the bank details on an order waiting for a transfer', function () {
    $order = Order::factory()->create(['payment_method' => 'bank_transfer']);

    $this->get($order->trackingUrl())->assertSee('Complete your payment by bank transfer');
});

it('downloads the PDF receipt through the signed link', function () {
    $order = Order::factory()->create();
    $order->items()->create(['name' => 'Glow Serum', 'price' => 1000000, 'quantity' => 1, 'line_total' => 1000000]);

    $this->get(route('orders.receipt', $order))->assertForbidden();
    $this->get($order->receiptUrl())->assertOk()->assertHeader('Content-Type', 'application/pdf');
});

it('finds an order by number and email', function () {
    $order = Order::factory()->create(['number' => 'KDS-ABC1234', 'email' => 'ada@example.com']);

    $this->get(route('orders.track', ['number' => 'kds-abc1234', 'email' => 'ADA@example.com']))
        ->assertRedirect($order->trackingUrl());
});

it('does not reveal an order when the email does not match', function () {
    Order::factory()->create(['number' => 'KDS-ABC1234', 'email' => 'ada@example.com']);

    $this->from(route('orders.track'))
        ->get(route('orders.track', ['number' => 'KDS-ABC1234', 'email' => 'someone@else.com']))
        ->assertRedirect(route('orders.track'))
        ->assertSessionHasErrors('number');
});

describe('customer account', function () {
    it('lists only the customer\'s own orders', function () {
        $user = User::factory()->create();
        $mine = Order::factory()->for($user)->create();
        $theirs = Order::factory()->for(User::factory())->create();

        $this->actingAs($user)->get(route('account.orders'))->assertSee($mine->number)->assertDontSee($theirs->number);
        $this->actingAs($user)->get(route('account.order', $mine))->assertOk();
        $this->actingAs($user)->get(route('account.order', $theirs))->assertNotFound();
    });

    it('needs a login', function () {
        $this->get(route('account.orders'))->assertRedirect(route('login'));
    });

    it('saves and removes wishlist items', function () {
        $user = User::factory()->create();
        $product = simpleProduct(['name' => 'Rose Perfume']);

        Livewire::actingAs($user)->test('pages::shop.home')->call('toggleWishlist', $product->id);
        expect($user->wishlist()->pluck('products.id')->all())->toBe([$product->id]);
        $this->actingAs($user)->get(route('account.wishlist'))->assertSee('Rose Perfume');

        Livewire::actingAs($user)->test('pages::shop.home')->call('toggleWishlist', $product->id);
        expect($user->wishlist()->count())->toBe(0);
    });

    it('sends guests to log in when they tap the wishlist heart', function () {
        Livewire::test('pages::shop.home')->call('toggleWishlist', simpleProduct()->id)->assertRedirect(route('login'));
    });

    it('adds, edits and removes addresses, keeping one default', function () {
        $user = User::factory()->create();

        $page = Livewire::actingAs($user)->test('pages::account.addresses')
            ->call('create')
            ->set('label', 'Home')->set('name', 'Ada Obi')->set('phone', '08031234567')
            ->set('address', '12 Allen Avenue')->set('city', 'Ikeja')->set('state', 'Lagos')
            ->call('save')
            ->assertHasNoErrors();

        $home = $user->addresses()->sole();
        expect($home->is_default)->toBeTrue();

        $page->call('create')
            ->set('label', 'Office')->set('phone', '08031234567')->set('address', '1 Marina')->set('city', 'Lagos Island')->set('state', 'Lagos')->set('isDefault', true)
            ->call('save');

        expect($home->fresh()->is_default)->toBeFalse()
            ->and($user->addresses()->where('is_default', true)->value('label'))->toBe('Office');

        $page->call('delete', $home->id);
        expect($user->addresses()->count())->toBe(1);
    });

    it('cannot touch another customer\'s address', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $address = $other->addresses()->create(['name' => 'X', 'phone' => '08031234567', 'address' => 'A', 'city' => 'B', 'state' => 'Lagos']);

        Livewire::actingAs($user)->test('pages::account.addresses')->call('delete', $address->id);

        expect($address->fresh())->not->toBeNull();
    });
});
