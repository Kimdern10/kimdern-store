<?php

use App\Livewire\Concerns\ReportsBankTransfer;
use App\Models\Order;
use App\Support\Cart;
use Flux\Flux;
use Illuminate\Validation\ValidationException;
use App\Support\Seo;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Order details')] class extends Component {
    use ReportsBankTransfer;

    public Order $order;

    public function mount(Order $order): void
    {
        abort_unless($order->user_id === Auth::id(), 404);

        $this->order = $order->load(['items.product', 'history']);
        Seo::set('Order '.$order->number)->noindex = true;
    }

    public function refreshOrder(): void
    {
        $this->order->refresh()->load(['items.product', 'history']);
    }

    /**
     * Put everything from this order that is still on sale back in the cart.
     */
    public function buyAgain(Cart $cart): void
    {
        $added = 0;
        $missed = [];

        foreach ($this->order->items as $item) {
            $product = $item->product;
            $variant = $item->product_variant_id ? $product?->variants()->find($item->product_variant_id) : null;

            if (! $product || ! $product->is_active || ($item->product_variant_id && ! $variant)) {
                $missed[] = $item->name;

                continue;
            }

            try {
                $cart->add($product, $variant, $item->quantity);
                $added++;
            } catch (ValidationException) {
                $missed[] = $item->name;
            }
        }

        if ($added === 0) {
            Flux::toast(variant: 'warning', text: 'Sorry, none of these items are available right now.');

            return;
        }

        if ($missed !== []) {
            session()->flash('buy_again_missed', $missed);
        }

        $this->redirectRoute('cart', navigate: true);
    }
}; ?>

<div @if ($order->status === \App\Enums\OrderStatus::Pending) wire:poll.20s="refreshOrder" @endif>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <a href="{{ route('account.orders') }}" wire:navigate.hover class="text-sm font-semibold text-brand-700 hover:underline dark:text-brand-400">← All orders</a>
            <h2 class="mt-2 text-xl font-bold">Order {{ $order->number }}</h2>
            <p class="text-sm text-zinc-500">Placed {{ $order->created_at->format('j F Y, g:i a') }}</p>
        </div>
        <div class="flex items-center gap-3">
            <flux:badge :color="$order->status->colour()">{{ $order->status->label() }}</flux:badge>
            <flux:button size="sm" variant="primary" icon="arrow-path" wire:click="buyAgain">Buy again</flux:button>
        </div>
    </div>

    <x-order-details :order="$order" class="mt-6" />
</div>
