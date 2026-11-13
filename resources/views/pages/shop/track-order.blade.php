<?php

use App\Models\Order;
use App\Support\Seo;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * "Track my order": find an order by its number and the email used to place it.
 */
new #[Layout('layouts::shop')] class extends Component {
    #[Url(except: '')]
    public string $number = '';

    public string $email = '';

    public function mount(): void
    {
        Seo::set('Track your order', 'Enter your order number and email to see where your Kimdern Store order is.');
    }

    public function find(): void
    {
        $this->validate([
            'number' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email'],
        ], [], ['number' => 'order number']);

        $key = 'track-order:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('number', 'Too many tries. Please wait a minute and try again.');

            return;
        }

        RateLimiter::hit($key, 60);

        $order = Order::query()
            ->where('number', strtoupper(trim($this->number)))
            ->where('email', strtolower(trim($this->email)))
            ->first();

        if (! $order) {
            $this->addError('number', "We couldn't find an order with that number and email.");

            return;
        }

        $this->redirect($order->trackingUrl(), navigate: true);
    }
}; ?>

<div class="container-shop max-w-xl pt-14">
    <div class="text-center" data-reveal>
        <div class="mx-auto grid size-16 place-items-center rounded-2xl bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-300"><flux:icon.truck class="size-8" /></div>
        <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-brand-950 sm:text-4xl dark:text-white">Track your order</h1>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">Enter the order number from your email (it starts with KDS-) and the email you used at checkout.</p>
    </div>

    <form wire:submit="find" class="mt-8 space-y-5 rounded-3xl border p-6 sm:p-8" data-reveal="100">
        <flux:input wire:model="number" label="Order number" placeholder="KDS-ABC1234" required class="uppercase" />
        <flux:input wire:model="email" type="email" label="Email address" required />
        <flux:button type="submit" variant="primary" class="w-full">
            <span wire:loading.remove wire:target="find">Find my order</span>
            <span wire:loading wire:target="find">Looking…</span>
        </flux:button>
    </form>

    <p class="mt-6 text-center text-sm text-zinc-500">Have an account? <a href="{{ route('account.orders') }}" wire:navigate.hover class="font-semibold text-brand-700 hover:underline dark:text-brand-400">See all your orders</a>.</p>
</div>
