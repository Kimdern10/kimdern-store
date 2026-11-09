<?php

use App\Models\Order;
use App\Support\Seo;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Order details')] class extends Component {
    public Order $order;

    public function mount(Order $order): void
    {
        abort_unless($order->user_id === Auth::id(), 404);

        $this->order = $order->load(['items.product', 'history']);
        Seo::set('Order '.$order->number)->noindex = true;
    }
}; ?>

<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('account.orders') }}" class="text-sm font-semibold text-brand-700 hover:underline dark:text-brand-400">← All orders</a>
            <h2 class="mt-2 text-xl font-bold">Order {{ $order->number }}</h2>
            <p class="text-sm text-zinc-500">Placed {{ $order->created_at->format('j F Y, g:i a') }}</p>
        </div>
        <flux:badge :color="$order->status->colour()">{{ $order->status->label() }}</flux:badge>
    </div>

    <x-order-details :order="$order" class="mt-6" />
</div>
