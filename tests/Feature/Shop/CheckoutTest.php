<?php

use App\Enums\OrderStatus;
use App\Mail\NewOrderAlert;
use App\Mail\OrderPlaced;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use App\Support\Cart;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    Mail::fake();
    lagosZone(fee: 250000);
});

function checkout(array $overrides = [])
{
    $component = Livewire::test('pages::shop.checkout');

    foreach (checkoutDetails($overrides) as $field => $value) {
        $component->set($field, $value);
    }

    return $component;
}

it('sends an empty cart back to the cart page', function () {
    $this->get(route('checkout'))->assertRedirect(route('cart'));
});

describe('bank transfer (no Paystack keys)', function () {
    beforeEach(fn () => config(['services.paystack.secret_key' => null]));

    it('places a guest order, takes the items out of stock and empties the cart', function () {
        $product = simpleProduct(['price' => 1000000, 'stock' => 5]);
        app(Cart::class)->add($product, null, 2);

        checkout()->call('placeOrder')->assertHasNoErrors()->assertRedirect();

        $order = Order::query()->sole();
        expect($order->status)->toBe(OrderStatus::Pending)
            ->and($order->payment_method)->toBe('bank_transfer')
            ->and($order->user_id)->toBeNull()
            ->and($order->subtotal)->toBe(2000000)
            ->and($order->delivery_fee)->toBe(250000)
            ->and($order->total)->toBe(2250000)
            ->and($order->items->sole()->quantity)->toBe(2)
            ->and($product->fresh()->stock)->toBe(3)
            ->and(app(Cart::class)->isEmpty())->toBeTrue();
    });

    it('emails the customer the bank details with a PDF receipt, and alerts the shop', function () {
        app(Cart::class)->add(simpleProduct());

        checkout()->call('placeOrder');

        Mail::assertSent(OrderPlaced::class, function (OrderPlaced $mail) {
            return $mail->hasTo('ada@example.com')
                && $mail->attachments()[0]->as === 'receipt-'.$mail->order->number.'.pdf';
        });
        Mail::assertSent(NewOrderAlert::class);
    });

    it('records the coupon and counts its use', function () {
        app(Cart::class)->add(simpleProduct(['price' => 2000000]));
        $coupon = Coupon::factory()->create(['code' => 'WELCOME10', 'value' => 10]);
        app(Cart::class)->applyCoupon('WELCOME10');

        checkout()->call('placeOrder');

        $order = Order::query()->sole();
        expect($order->discount)->toBe(200000)
            ->and($order->coupon_code)->toBe('WELCOME10')
            ->and($order->total)->toBe(2000000 - 200000 + 250000)
            ->and($coupon->fresh()->used_count)->toBe(1);
    });

    it('takes a sold size out of that size\'s stock only', function () {
        $product = sizedProduct(['M' => 4, 'L' => 6]);
        app(Cart::class)->add($product, $product->variants->firstWhere('size', 'M'), 3);

        checkout()->call('placeOrder');

        expect($product->variants->firstWhere('size', 'M')->fresh()->stock)->toBe(1)
            ->and($product->variants->firstWhere('size', 'L')->fresh()->stock)->toBe(6)
            ->and(Order::query()->sole()->items->sole()->variant_label)->toBe('Size M');
    });

    it('links the order to a logged-in customer and saves their address', function () {
        $user = User::factory()->create(['phone' => null]);
        $this->actingAs($user);
        app(Cart::class)->add(simpleProduct());

        checkout()->call('placeOrder');

        expect(Order::query()->sole()->user_id)->toBe($user->id)
            ->and($user->addresses()->sole()->city)->toBe('Ikeja')
            ->and($user->fresh()->phone)->toBe('08031234567');
    });

    it('only orders what is left when stock drops after the item was added', function () {
        $product = simpleProduct(['stock' => 2]);
        app(Cart::class)->add($product, null, 2);
        $component = checkout();

        $product->update(['stock' => 1]); // someone else bought one meanwhile
        app()->forgetScopedInstances(); // the next click is a new request with a fresh cart

        $component->call('placeOrder');

        expect(Order::query()->sole()->items->sole()->quantity)->toBe(1)
            ->and($product->fresh()->stock)->toBe(0);
    });

    it('places no order when everything in the cart sold out', function () {
        $product = simpleProduct(['stock' => 1]);
        app(Cart::class)->add($product);
        $component = checkout();

        $product->update(['stock' => 0]);
        app()->forgetScopedInstances();

        $component->call('placeOrder')->assertHasErrors('cart');
        expect(Order::query()->count())->toBe(0);
    });

    it('refuses a state with no delivery zone', function () {
        app(Cart::class)->add(simpleProduct());

        checkout(['state' => 'Kano'])->call('placeOrder')->assertHasErrors('state');

        expect(Order::query()->count())->toBe(0);
    });

    it('validates the contact details', function () {
        app(Cart::class)->add(simpleProduct());

        checkout(['email' => 'not-an-email', 'phone' => '12', 'state' => 'Atlantis'])
            ->call('placeOrder')
            ->assertHasErrors(['email', 'phone', 'state']);
    });
});

describe('Paystack', function () {
    beforeEach(fn () => config(['services.paystack.secret_key' => 'sk_test_example']));

    it('starts a Paystack payment for the order total in kobo and sends the customer there', function () {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/abc123']]),
        ]);
        app(Cart::class)->add(simpleProduct(['price' => 1000000]));

        checkout()->call('placeOrder')->assertRedirect('https://checkout.paystack.com/abc123');

        $order = Order::query()->sole();
        expect($order->payment_method)->toBe('paystack')
            ->and($order->payment_reference)->toStartWith($order->number.'-');

        Http::assertSent(fn ($request) => $request->url() === 'https://api.paystack.co/transaction/initialize'
            && $request['amount'] === 1250000
            && $request['email'] === 'ada@example.com'
            && $request['reference'] === $order->payment_reference
            && $request->hasHeader('Authorization', 'Bearer sk_test_example'));

        // Card orders are confirmed by email only once paid.
        Mail::assertNotSent(OrderPlaced::class);
    });

    it('shows the order page when Paystack cannot be reached', function () {
        Http::fake(['api.paystack.co/*' => Http::response(['status' => false, 'message' => 'Down'], 500)]);
        app(Cart::class)->add(simpleProduct());

        checkout()->call('placeOrder')->assertRedirectContains('/orders/');

        expect(Order::query()->sole()->status)->toBe(OrderStatus::Pending);
    });
});
