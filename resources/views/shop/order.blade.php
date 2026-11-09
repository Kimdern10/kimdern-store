<x-layouts::shop :title="'Order '.$order->number">
    <div class="container-shop max-w-5xl pt-10">
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

        @if (session('payment_failed'))
            <flux:callout variant="warning" icon="exclamation-triangle" class="mt-8" heading="Your payment didn't go through" text="No money was taken. You can try again below." />
        @endif

        @if ($retryUrl)
            <div class="mt-8 flex flex-col items-center gap-3 rounded-3xl border-2 border-amber-300 bg-amber-50 p-6 text-center dark:border-amber-700 dark:bg-amber-950/30">
                <p class="font-semibold">This order is waiting for payment of {{ naira($order->total) }}.</p>
                <flux:button variant="primary" :href="$retryUrl" icon="credit-card">Pay now with Paystack</flux:button>
            </div>
        @endif

        <x-order-details :order="$order" class="mt-10" />

        <div class="mt-10 flex flex-wrap justify-center gap-3">
            <flux:button href="{{ route('shop') }}" variant="primary">Continue shopping</flux:button>
            @auth
                <flux:button href="{{ route('account.orders') }}">My orders</flux:button>
            @else
                <flux:button href="{{ route('register') }}">Create an account to track orders easily</flux:button>
            @endauth
        </div>
    </div>
</x-layouts::shop>
