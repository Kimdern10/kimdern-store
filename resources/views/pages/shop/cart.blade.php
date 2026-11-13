<?php

use App\Support\Cart;
use App\Support\Seo;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

new #[Layout('layouts::shop')] class extends Component {
    public string $couponCode = '';

    public function mount(): void
    {
        Seo::set('Your cart')->noindex = true;
    }

    #[Computed]
    public function cart(): Cart
    {
        return app(Cart::class);
    }

    #[On('cart-updated')]
    public function refreshCart(): void
    {
        unset($this->cart);
    }

    public function changeQuantity(string $key, int $quantity): void
    {
        $this->cart->update($key, $quantity);
        $this->dispatch('cart-updated');
    }

    public function removeLine(string $key): void
    {
        $this->cart->remove($key);
        $this->dispatch('cart-updated');
    }

    public function applyCoupon(): void
    {
        $this->resetErrorBag('couponCode');

        if (trim($this->couponCode) === '') {
            return;
        }

        if ($problem = $this->cart->applyCoupon($this->couponCode)) {
            $this->addError('couponCode', $problem);

            return;
        }

        $this->couponCode = '';
        unset($this->cart);
    }

    public function removeCoupon(): void
    {
        $this->cart->removeCoupon();
        unset($this->cart);
    }
}; ?>

<div class="container-shop pt-10">
    <h1 class="text-3xl font-extrabold tracking-tight text-brand-950 sm:text-4xl dark:text-white">Your cart</h1>

    @if ($missed = session('buy_again_missed'))
        <flux:callout class="mt-6" variant="warning" icon="exclamation-triangle" heading="Some items could not be added" :text="'Not available right now: '.implode(', ', $missed).'.'" />
    @endif

    @if ($this->cart->isEmpty())
        <div class="mt-10 rounded-[2rem] border border-dashed p-12 text-center">
            <div class="mx-auto grid size-20 place-items-center rounded-full bg-brand-50 text-brand-700 dark:bg-brand-950 dark:text-brand-400"><flux:icon.shopping-bag class="size-9" /></div>
            <p class="mt-5 text-xl font-bold">Your cart is empty</p>
            <p class="mt-1 text-zinc-500">Looks like you haven't added anything yet.</p>
            <flux:button variant="primary" class="mt-6" href="{{ route('shop') }}" wire:navigate.hover>Start shopping</flux:button>
        </div>
    @else
        <div class="mt-8 grid gap-10 lg:grid-cols-[1fr_24rem]">
            <ul class="divide-y rounded-3xl border">
                @foreach ($this->cart->lines() as $line)
                    <li wire:key="line-{{ $line->key }}" class="flex gap-4 p-4 sm:gap-6 sm:p-6">
                        <a href="{{ route('product', $line->product) }}" wire:navigate.hover class="shrink-0"><img src="{{ $line->product->imageUrl() }}" alt="{{ $line->product->name }}" class="size-24 rounded-2xl object-cover sm:size-28"></a>
                        <div class="flex min-w-0 flex-1 flex-col gap-1">
                            <div class="flex justify-between gap-4">
                                <div class="min-w-0">
                                    <a href="{{ route('product', $line->product) }}" wire:navigate.hover class="font-bold hover:text-brand-700 dark:hover:text-brand-400">{{ $line->product->name }}</a>
                                    @if ($line->variantLabel())<p class="text-sm text-zinc-500">{{ $line->variantLabel() }}</p>@endif
                                    <p class="text-sm text-zinc-500">{{ naira($line->unitPrice()) }} each</p>
                                </div>
                                <p class="shrink-0 font-extrabold">{{ naira($line->total()) }}</p>
                            </div>
                            <div class="mt-auto flex items-center justify-between pt-3">
                                <div class="flex items-center rounded-full border">
                                    <button type="button" wire:click="changeQuantity('{{ $line->key }}', {{ $line->quantity - 1 }})" class="grid size-9 place-items-center rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="One less"><flux:icon.minus variant="micro" /></button>
                                    <span class="w-8 text-center font-semibold">{{ $line->quantity }}</span>
                                    <button type="button" wire:click="changeQuantity('{{ $line->key }}', {{ $line->quantity + 1 }})" @disabled($line->quantity >= $line->available()) class="grid size-9 place-items-center rounded-full hover:bg-zinc-100 disabled:opacity-30 dark:hover:bg-zinc-800" aria-label="One more"><flux:icon.plus variant="micro" /></button>
                                </div>
                                <button type="button" wire:click="removeLine('{{ $line->key }}')" class="flex items-center gap-1 text-sm text-zinc-500 hover:text-red-600"><flux:icon.trash variant="micro" /> Remove</button>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            <aside class="h-fit space-y-5 rounded-3xl border bg-zinc-50/70 p-6 lg:sticky lg:top-28 dark:bg-zinc-900/50">
                <h2 class="text-lg font-bold">Order summary</h2>

                @if ($coupon = $this->cart->coupon())
                    <div class="flex items-center justify-between rounded-2xl bg-brand-50 px-4 py-3 text-sm dark:bg-brand-950">
                        <span><flux:icon.tag variant="micro" class="inline text-brand-700" /> <strong>{{ $coupon->code }}</strong> · {{ $coupon->summary() }}</span>
                        <button type="button" wire:click="removeCoupon" class="text-xs font-semibold text-zinc-500 hover:text-red-600">Remove</button>
                    </div>
                @else
                    <form wire:submit="applyCoupon" class="flex gap-2">
                        <flux:input wire:model="couponCode" placeholder="Discount code" aria-label="Discount code" class="uppercase" />
                        <flux:button type="submit">Apply</flux:button>
                    </form>
                    @error('couponCode')<p class="-mt-3 text-sm text-red-600">{{ $message }}</p>@enderror
                @endif

                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between"><dt>Subtotal</dt><dd class="font-semibold">{{ naira($this->cart->subtotal()) }}</dd></div>
                    @if ($this->cart->discount() > 0)
                        <div class="flex justify-between text-brand-700 dark:text-brand-400"><dt>Discount</dt><dd class="font-semibold">−{{ naira($this->cart->discount()) }}</dd></div>
                    @endif
                    <div class="flex justify-between text-zinc-500"><dt>Delivery</dt><dd>Worked out at checkout</dd></div>
                </dl>
                <div class="flex justify-between border-t pt-4 text-lg font-extrabold"><span>Total</span><span>{{ naira($this->cart->subtotal() - $this->cart->discount()) }}</span></div>

                <flux:button variant="primary" href="{{ route('checkout') }}" class="w-full" icon-trailing="lock-closed" wire:navigate.hover>Go to checkout</flux:button>
                <a href="{{ route('shop') }}" wire:navigate.hover class="block text-center text-sm font-semibold text-brand-700 hover:underline dark:text-brand-400">← Continue shopping</a>
            </aside>
        </div>
    @endif
</div>
