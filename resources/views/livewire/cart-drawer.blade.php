<?php

use App\Support\Cart;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The cart button in the header and the slide-out cart. Opens by itself when something is added.
 */
new class extends Component {
    #[On('cart-updated')]
    public function refreshCart(): void
    {
        unset($this->cart);
    }

    #[Computed]
    public function cart(): Cart
    {
        return app(Cart::class);
    }

    public function changeQuantity(string $key, int $quantity): void
    {
        $this->cart->update($key, $quantity);
        unset($this->cart);
        $this->dispatch('cart-updated');
    }

    public function removeLine(string $key): void
    {
        $this->cart->remove($key);
        unset($this->cart);
        $this->dispatch('cart-updated');
    }
}; ?>

<div x-data="{ open: false }" x-on:cart-updated.window="if ($event.detail?.open) open = true" x-on:open-cart.window="open = true" x-on:keydown.escape.window="open = false">
    <button type="button" x-on:click="open = true" class="relative grid size-10 place-items-center rounded-full transition hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Open cart ({{ $this->cart->count() }} items)">
        <flux:icon.shopping-bag variant="outline" class="size-5" />
        @if ($this->cart->count() > 0)
            <span wire:key="cart-count-{{ $this->cart->count() }}" class="absolute -end-0.5 -top-0.5 grid min-w-5 animate-[bounce_0.6s_ease-out_1] place-items-center rounded-full bg-amber-500 px-1 text-[11px] font-bold text-brand-950">{{ $this->cart->count() }}</span>
        @endif
    </button>

    <template x-teleport="body">
        <div x-cloak x-show="open" class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Your cart">
            <div x-show="open" x-transition.opacity.duration.300ms class="absolute inset-0 bg-zinc-950/50 backdrop-blur-sm" x-on:click="open = false"></div>

            <aside x-show="open" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-x-full" x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="translate-x-full" class="absolute inset-y-0 end-0 flex w-full max-w-md flex-col bg-white shadow-2xl dark:bg-zinc-900">
                <div class="flex items-center justify-between border-b px-5 py-4">
                    <h2 class="text-lg font-bold">Your cart <span class="text-zinc-400">({{ $this->cart->count() }})</span></h2>
                    <button type="button" x-on:click="open = false" class="grid size-10 place-items-center rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Close cart">
                        <flux:icon.x-mark />
                    </button>
                </div>

                @if ($this->cart->isEmpty())
                    <div class="flex flex-1 flex-col items-center justify-center gap-4 p-8 text-center">
                        <div class="grid size-20 place-items-center rounded-full bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-400">
                            <flux:icon.shopping-bag class="size-9" />
                        </div>
                        <p class="text-lg font-semibold">Your cart is empty</p>
                        <p class="text-sm text-zinc-500">Find something you love and it will show up here.</p>
                        <flux:button variant="primary" href="{{ route('shop') }}">Start shopping</flux:button>
                    </div>
                @else
                    @php($subtotal = $this->cart->subtotal())
                    @php($freeOver = \App\Models\DeliveryZone::query()->where('is_active', true)->whereNotNull('free_over')->min('free_over'))
                    @if ($freeOver)
                        <div class="border-b bg-brand-50/60 px-5 py-3 text-sm dark:bg-brand-950/40">
                            @if ($subtotal >= $freeOver)
                                <p class="font-semibold text-brand-800 dark:text-brand-300">🎉 You qualify for free delivery in Lagos!</p>
                            @else
                                <p>Add <strong>{{ naira($freeOver - $subtotal) }}</strong> more for free delivery in Lagos.</p>
                            @endif
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-brand-100 dark:bg-brand-900">
                                <div class="h-full rounded-full bg-gradient-to-r from-brand-500 to-amber-400 transition-all duration-700" style="width: {{ min(100, round($subtotal / $freeOver * 100)) }}%"></div>
                            </div>
                        </div>
                    @endif

                    <ul class="flex-1 divide-y overflow-y-auto px-5">
                        @foreach ($this->cart->lines() as $line)
                            <li wire:key="drawer-{{ $line->key }}" class="flex gap-4 py-4">
                                <a href="{{ route('product', $line->product) }}" class="shrink-0">
                                    <img src="{{ $line->product->imageUrl() }}" alt="{{ $line->product->name }}" class="size-20 rounded-xl object-cover" loading="lazy">
                                </a>
                                <div class="flex min-w-0 flex-1 flex-col">
                                    <a href="{{ route('product', $line->product) }}" class="truncate font-semibold hover:text-brand-700 dark:hover:text-brand-400">{{ $line->product->name }}</a>
                                    @if ($line->variantLabel())
                                        <p class="text-xs text-zinc-500">{{ $line->variantLabel() }}</p>
                                    @endif
                                    <p class="mt-1 text-sm font-bold">{{ naira($line->total()) }}</p>
                                    <div class="mt-auto flex items-center justify-between pt-2">
                                        <div class="flex items-center rounded-full border">
                                            <button type="button" wire:click="changeQuantity('{{ $line->key }}', {{ $line->quantity - 1 }})" class="grid size-8 place-items-center rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="One less">
                                                <flux:icon.minus variant="micro" />
                                            </button>
                                            <span class="w-7 text-center text-sm font-semibold">{{ $line->quantity }}</span>
                                            <button type="button" wire:click="changeQuantity('{{ $line->key }}', {{ $line->quantity + 1 }})" @disabled($line->quantity >= $line->available()) class="grid size-8 place-items-center rounded-full hover:bg-zinc-100 disabled:opacity-30 dark:hover:bg-zinc-800" aria-label="One more">
                                                <flux:icon.plus variant="micro" />
                                            </button>
                                        </div>
                                        <button type="button" wire:click="removeLine('{{ $line->key }}')" class="text-xs font-medium text-zinc-500 underline-offset-2 hover:text-red-600 hover:underline">Remove</button>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="space-y-3 border-t px-5 py-5">
                        <div class="flex items-center justify-between text-base">
                            <span class="font-medium">Subtotal</span>
                            <span class="text-lg font-extrabold">{{ naira($subtotal) }}</span>
                        </div>
                        <p class="text-xs text-zinc-500">Delivery and discount codes are worked out at checkout.</p>
                        <flux:button variant="primary" href="{{ route('checkout') }}" class="w-full" icon-trailing="arrow-right">Checkout</flux:button>
                        <flux:button variant="ghost" href="{{ route('cart') }}" class="w-full">View cart</flux:button>
                    </div>
                @endif
            </aside>
        </div>
    </template>
</div>
