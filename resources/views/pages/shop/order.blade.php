<?php

use App\Enums\OrderStatus;
use App\Livewire\Concerns\ReportsBankTransfer;
use App\Models\Order;
use App\Services\Paystack;
use App\Support\Seo;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The order page customers (and guests) reach through the signed link in their emails.
 * It checks for updates every 30 seconds, so a payment or shipping update shows without reloading.
 */
new #[Layout('layouts::shop')] class extends Component {
    use ReportsBankTransfer;

    public Order $order;

    public bool $justPlaced = false;

    public bool $paymentFailed = false;

    public function mount(Order $order): void
    {
        $this->order = $order->load(['items.product', 'history']);
        $this->paymentFailed = (bool) session('payment_failed');
        $this->justPlaced = $order->created_at->gt(now()->subMinutes(30)) && ! $this->paymentFailed;

        Seo::set('Order '.$order->number)->noindex = true;
    }

    public function refreshOrder(): void
    {
        $this->order->refresh()->load(['items.product', 'history']);
    }

    public function retryUrl(string $via = 'transfer'): ?string
    {
        return $this->order->status === OrderStatus::Pending && $this->order->payment_method === 'paystack' && app(Paystack::class)->isConfigured()
            ? URL::signedRoute('orders.pay', ['order' => $this->order, 'via' => $via])
            : null;
    }
}; ?>

<div class="container-shop max-w-5xl pt-10" @if ($order->awaitingTransferCheck()) wire:poll.10s="refreshOrder" @elseif (! in_array($order->status, [OrderStatus::Delivered, OrderStatus::Cancelled], true)) wire:poll.30s="refreshOrder" @endif>
    <div class="text-center" data-reveal>
        @if ($justPlaced)
            <div class="mx-auto grid size-20 place-items-center rounded-full bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-300">
                <flux:icon.check class="size-10" />
            </div>
            <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-brand-950 sm:text-4xl dark:text-white">
                {{ $order->paid_at ? 'Thank you! Your order is confirmed.' : 'Thank you! Your order is in.' }}
            </h1>
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">We've emailed the details to <strong>{{ $order->email }}</strong>. Order number <strong>{{ $order->number }}</strong>.</p>
        @else
            <p class="text-sm font-bold tracking-widest text-amber-600 uppercase">Order {{ $order->number }}</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-brand-950 sm:text-4xl dark:text-white">{{ $order->status->label() }}</h1>
            <p class="mt-2 text-zinc-500">Placed on {{ $order->created_at->format('j F Y, g:i a') }}</p>
        @endif
    </div>

    @if ($paymentFailed)
        <flux:callout variant="warning" icon="exclamation-triangle" class="mt-8" heading="Your payment didn't go through" text="No money was taken. You can try again below." />
    @endif

    @if ($retry = $this->retryUrl())
        <div class="mt-8 flex flex-col items-center gap-3 rounded-3xl border-2 border-amber-300 bg-amber-50 p-6 text-center dark:border-amber-700 dark:bg-amber-950/30">
            <p class="font-semibold">This order is waiting for payment of {{ naira($order->total) }}.</p>
            <div class="flex flex-wrap justify-center gap-3">
                <flux:button variant="primary" :href="$this->retryUrl('transfer')" icon="building-library">Pay by bank transfer</flux:button>
                <flux:button :href="$this->retryUrl('card')" icon="credit-card">Pay by card or USSD</flux:button>
            </div>
            <p class="text-xs text-zinc-500">Bank transfers from OPay, Moniepoint, Kuda or any bank are confirmed automatically.</p>
        </div>
    @endif

    <x-order-details :order="$order" class="mt-10" />

    <div class="mt-10 flex flex-wrap justify-center gap-3">
        <flux:button href="{{ route('shop') }}" wire:navigate variant="primary">Continue shopping</flux:button>
        @auth
            <flux:button href="{{ route('account.orders') }}" wire:navigate>My orders</flux:button>
        @else
            <flux:button href="{{ route('register') }}">Create an account to track orders easily</flux:button>
        @endauth
    </div>
</div>
