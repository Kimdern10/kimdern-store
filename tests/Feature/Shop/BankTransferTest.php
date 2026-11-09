<?php

use App\Enums\OrderStatus;
use App\Mail\OrderStatusChanged;
use App\Mail\TransferReported;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(fn () => Mail::fake());

describe('direct bank transfer (no Paystack keys)', function () {
    it('shows an "I have sent the money" button instead of asking for a WhatsApp receipt', function () {
        $order = Order::factory()->create(['payment_method' => 'bank_transfer']);

        $this->get($order->trackingUrl())
            ->assertSee('I have sent the money')
            ->assertDontSee('proof of payment');
    });

    it('records the customer\'s "I have sent the money" and alerts the shop at once', function () {
        Setting::put(['email' => 'owner@kimdern.test']);
        $order = Order::factory()->create(['payment_method' => 'bank_transfer']);

        Livewire::test('pages::shop.order', ['order' => $order])
            ->call('reportTransfer')
            ->assertSee("We're confirming your payment", false);

        $order->refresh();
        expect($order->transfer_reported_at)->not->toBeNull()
            ->and($order->status)->toBe(OrderStatus::Pending)
            ->and($order->history()->where('note', 'like', '%bank transfer has been sent%')->exists())->toBeTrue();
        Mail::assertSent(TransferReported::class, fn ($mail) => $mail->hasTo('owner@kimdern.test'));
    });

    it('only alerts the shop once and ignores paid or Paystack orders', function () {
        $order = Order::factory()->create(['payment_method' => 'bank_transfer']);
        $page = Livewire::test('pages::shop.order', ['order' => $order]);
        $page->call('reportTransfer');
        $page->call('reportTransfer');

        Livewire::test('pages::shop.order', ['order' => Order::factory()->paid()->create(['payment_method' => 'bank_transfer'])])->call('reportTransfer');
        Livewire::test('pages::shop.order', ['order' => Order::factory()->create(['payment_method' => 'paystack'])])->call('reportTransfer');

        Mail::assertSent(TransferReported::class, 1);
    });

    it('lets the owner confirm the money in one tap, which emails the receipt', function () {
        $order = Order::factory()->create(['payment_method' => 'bank_transfer', 'transfer_reported_at' => now()]);

        Livewire::actingAs(User::factory()->admin()->create())->test('pages::admin.order', ['order' => $order])
            ->assertSee('Money received')
            ->call('confirmTransfer');

        expect($order->fresh())->status->toBe(OrderStatus::Paid)->paid_at->not->toBeNull();
        Mail::assertSent(OrderStatusChanged::class, fn ($mail) => $mail->hasTo($order->email) && count($mail->attachments()) === 1);
    });

    it('updates the customer\'s page by itself once the payment is confirmed', function () {
        $order = Order::factory()->create(['payment_method' => 'bank_transfer', 'transfer_reported_at' => now()]);
        $page = Livewire::test('pages::shop.order', ['order' => $order])->assertSee("We're confirming your payment", false);

        $order->update(['status' => OrderStatus::Paid, 'paid_at' => now()]);

        $page->call('refreshOrder')->assertDontSee("We're confirming your payment", false)->assertSee('Payment received. Thank you!');
    });
});

describe('Paystack bank transfer', function () {
    beforeEach(fn () => config(['services.paystack.secret_key' => 'sk_test_example']));

    it('marks a transfer as paid automatically when Paystack confirms it', function () {
        $order = Order::factory()->create(['payment_method' => 'paystack', 'payment_channel' => 'transfer', 'payment_reference' => 'KDS-T-1', 'total' => 500000]);
        Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'amount' => 500000, 'currency' => 'NGN', 'channel' => 'bank_transfer']])]);

        $this->get(route('checkout.callback', ['reference' => 'KDS-T-1']));

        $order->refresh();
        expect($order->status)->toBe(OrderStatus::Paid)
            ->and($order->paymentMethodLabel())->toBe('Bank transfer via Paystack');
    });

    it('lets the customer try again by transfer or by card from the order page', function () {
        Http::fake(['api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/again']])]);
        $order = Order::factory()->create(['payment_method' => 'paystack']);

        $this->get(URL::signedRoute('orders.pay', ['order' => $order, 'via' => 'card']))->assertRedirect('https://checkout.paystack.com/again');
        Http::assertSent(fn ($request) => $request['channels'] === ['card', 'ussd', 'bank']);

        // Changing the link after it was signed is refused.
        $this->get(URL::signedRoute('orders.pay', ['order' => $order, 'via' => 'card']).'&via=transfer')->assertForbidden();
    });
});
