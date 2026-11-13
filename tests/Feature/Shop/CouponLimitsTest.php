<?php

use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use App\Support\Cart;
use App\Support\CustomerIdentity;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    config(['services.paystack.secret_key' => null]);
    lagosZone();
    $this->welcome = Coupon::factory()->create(['code' => 'WELCOME10', 'value' => 10, 'first_order_only' => true, 'once_per_customer' => true]);
});

function checkoutWith(array $details)
{
    $page = Livewire::test('pages::shop.checkout');

    foreach (checkoutDetails($details) as $field => $value) {
        $page->set($field, $value);
    }

    return $page;
}

it('treats Gmail dots, "+extra" and phone formats as the same person', function () {
    expect(CustomerIdentity::email('Ada.Obi+promo@GoogleMail.com'))->toBe('adaobi@gmail.com')
        ->and(CustomerIdentity::email('ada.obi@yahoo.com'))->toBe('ada.obi@yahoo.com')
        ->and(CustomerIdentity::phone('+234 803 123 4567'))->toBe(CustomerIdentity::phone('0803-123-4567'));
});

it('lets a new customer use WELCOME10 on their first order', function () {
    app(Cart::class)->add(simpleProduct(['price' => 1000000]));

    checkoutWith(['email' => 'new@example.com'])
        ->set('couponCode', 'WELCOME10')->call('applyCoupon')->assertHasNoErrors()
        ->call('placeOrder')->assertHasNoErrors();

    expect(Order::query()->sole()->discount)->toBe(100000);
});

it('refuses WELCOME10 to someone who has paid for an order before, even with a disguised email', function () {
    Order::factory()->paid()->create(['email' => 'adaobi@gmail.com', 'phone' => '08031234567']);
    app(Cart::class)->add(simpleProduct());

    checkoutWith(['email' => 'Ada.Obi+again@gmail.com', 'phone' => '07099999999'])
        ->set('couponCode', 'WELCOME10')
        ->call('applyCoupon')
        ->assertHasErrors('couponCode');

    expect(app(Cart::class)->coupon())->toBeNull();
});

it('refuses it when only the phone number matches a past customer', function () {
    Order::factory()->paid()->create(['email' => 'first@example.com', 'phone' => '+234 803 123 4567']);
    app(Cart::class)->add(simpleProduct());

    checkoutWith(['email' => 'brand-new@example.com', 'phone' => '0803 123 4567'])
        ->set('couponCode', 'WELCOME10')
        ->call('applyCoupon')
        ->assertHasErrors('couponCode');
});

it('takes the code off at the last step if the details typed after applying it belong to a past customer', function () {
    Order::factory()->paid()->create(['email' => 'repeat@example.com']);
    $cart = app(Cart::class);
    $cart->add(simpleProduct(['price' => 1000000]));
    $cart->applyCoupon('WELCOME10'); // applied on the cart page before we knew who they are

    checkoutWith(['email' => 'repeat@example.com'])
        ->call('placeOrder')
        ->assertHasErrors('cart');

    expect(Order::query()->where('email', 'repeat@example.com')->whereNotNull('coupon_code')->exists())->toBeFalse()
        ->and($cart->coupon())->toBeNull();
});

it('does not count unpaid or cancelled orders', function () {
    Order::factory()->create(['email' => 'tried@example.com']); // never paid
    Order::factory()->paid()->create(['email' => 'tried@example.com', 'status' => 'cancelled']);
    app(Cart::class)->add(simpleProduct());

    checkoutWith(['email' => 'tried@example.com'])
        ->set('couponCode', 'WELCOME10')->call('applyCoupon')
        ->assertHasNoErrors();
});

it('lets each customer use a "once per customer" code a single time', function () {
    Coupon::factory()->create(['code' => 'SALLAH', 'value' => 5, 'once_per_customer' => true]);
    Order::factory()->paid()->create(['email' => 'loyal@example.com', 'coupon_code' => 'SALLAH']);
    Order::factory()->paid()->create(['email' => 'other@example.com']);
    app(Cart::class)->add(simpleProduct());

    expect(app(Cart::class)->applyCoupon('SALLAH', 'loyal@example.com'))->toContain('already used SALLAH')
        ->and(app(Cart::class)->applyCoupon('SALLAH', 'other@example.com'))->toBeNull();
});

it('checks logged-in customers on the cart page straight away', function () {
    $user = User::factory()->create();
    Order::factory()->paid()->for($user)->create(['email' => 'different@example.com']);
    app(Cart::class)->add(simpleProduct());

    Livewire::actingAs($user)->test('pages::shop.cart')
        ->set('couponCode', 'WELCOME10')->call('applyCoupon')
        ->assertHasErrors('couponCode');
});

it('lets the owner switch the limits on in the admin', function () {
    Livewire::actingAs(User::factory()->admin()->create())->test('pages::admin.coupons')
        ->call('edit', $this->welcome->id)
        ->assertSet('first_order_only', true)
        ->set('once_per_customer', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($this->welcome->fresh()->once_per_customer)->toBeFalse()->and($this->welcome->fresh()->first_order_only)->toBeTrue();
});
