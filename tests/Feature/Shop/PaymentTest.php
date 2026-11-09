<?php

use App\Enums\OrderStatus;
use App\Mail\OrderPlaced;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    config(['services.paystack.secret_key' => 'sk_test_example']);
    Mail::fake();

    $this->order = Order::factory()->create(['payment_method' => 'paystack', 'payment_reference' => 'KDS-TEST-ABC123', 'total' => 1250000]);
});

function fakePaystackVerify(string $status = 'success', int $amount = 1250000): void
{
    Http::fake([
        'api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => $status, 'amount' => $amount, 'currency' => 'NGN', 'reference' => 'KDS-TEST-ABC123']]),
    ]);
}

function signedWebhook(array $event, string $secret = 'sk_test_example'): array
{
    $payload = json_encode($event);

    return [$payload, ['x-paystack-signature' => hash_hmac('sha512', $payload, $secret), 'Content-Type' => 'application/json']];
}

describe('callback', function () {
    it('marks the order paid after checking with Paystack, and emails the receipt', function () {
        fakePaystackVerify();

        $this->get(route('checkout.callback', ['reference' => 'KDS-TEST-ABC123']))
            ->assertRedirect($this->order->trackingUrl());

        $order = $this->order->fresh();
        expect($order->status)->toBe(OrderStatus::Paid)
            ->and($order->paid_at)->not->toBeNull()
            ->and($order->history()->where('status', 'paid')->exists())->toBeTrue();

        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/transaction/verify/KDS-TEST-ABC123'));
        Mail::assertSent(OrderPlaced::class, fn ($mail) => $mail->hasTo($order->email));
    });

    it('does not mark the order paid when Paystack says the payment failed', function () {
        fakePaystackVerify(status: 'failed');

        $this->get(route('checkout.callback', ['reference' => 'KDS-TEST-ABC123']))
            ->assertSessionHas('payment_failed');

        expect($this->order->fresh()->status)->toBe(OrderStatus::Pending);
        Mail::assertNothingSent();
    });

    it('does not mark the order paid when the amount paid is different', function () {
        fakePaystackVerify(amount: 100);

        $this->get(route('checkout.callback', ['reference' => 'KDS-TEST-ABC123']));

        expect($this->order->fresh()->paid_at)->toBeNull();
    });

    it('ignores an unknown reference', function () {
        Http::fake();

        $this->get(route('checkout.callback', ['reference' => 'nope']))->assertRedirect(route('home'));

        Http::assertNothingSent();
    });
});

describe('webhook', function () {
    it('rejects a request without a valid Paystack signature', function () {
        Http::fake();
        [$payload, $headers] = signedWebhook(['event' => 'charge.success', 'data' => ['reference' => 'KDS-TEST-ABC123']], 'wrong-secret');

        $this->call('POST', route('paystack.webhook'), [], [], [], $this->transformHeadersToServerVars($headers), $payload)
            ->assertUnauthorized();

        expect($this->order->fresh()->paid_at)->toBeNull();
        Http::assertNothingSent();
    });

    it('marks the order paid on a signed charge.success event', function () {
        fakePaystackVerify();
        [$payload, $headers] = signedWebhook(['event' => 'charge.success', 'data' => ['reference' => 'KDS-TEST-ABC123']]);

        $this->call('POST', route('paystack.webhook'), [], [], [], $this->transformHeadersToServerVars($headers), $payload)
            ->assertOk();

        expect($this->order->fresh()->status)->toBe(OrderStatus::Paid);
    });

    it('only records the payment once when the callback and webhook both arrive', function () {
        fakePaystackVerify();
        [$payload, $headers] = signedWebhook(['event' => 'charge.success', 'data' => ['reference' => 'KDS-TEST-ABC123']]);

        $this->get(route('checkout.callback', ['reference' => 'KDS-TEST-ABC123']));
        $this->call('POST', route('paystack.webhook'), [], [], [], $this->transformHeadersToServerVars($headers), $payload)->assertOk();

        expect($this->order->history()->where('status', 'paid')->count())->toBe(1);
        Mail::assertSentCount(2); // receipt to the customer + alert to the shop, once.
    });

    it('ignores other events', function () {
        Http::fake();
        [$payload, $headers] = signedWebhook(['event' => 'transfer.success', 'data' => ['reference' => 'KDS-TEST-ABC123']]);

        $this->call('POST', route('paystack.webhook'), [], [], [], $this->transformHeadersToServerVars($headers), $payload)->assertOk();

        expect($this->order->fresh()->paid_at)->toBeNull();
    });
});
