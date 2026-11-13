<?php

use App\Models\DeliveryZone;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Delivery fees, times and the returns policy, with a "how much to my state?" calculator.
 */
new #[Layout('layouts::shop')] class extends Component {
    public string $state = '';

    public string $basket = '';

    public function mount(): void
    {
        Seo::set('Delivery & returns', 'Delivery fees and times to all 36 states and Abuja, and our 7-day returns policy.');
    }

    /** @return Collection<int, DeliveryZone> */
    #[Computed]
    public function zones(): Collection
    {
        return DeliveryZone::query()->where('is_active', true)->orderBy('sort_order')->get();
    }

    /**
     * @return array{zone: DeliveryZone, fee: int}|null
     */
    #[Computed]
    public function quote(): ?array
    {
        $zone = DeliveryZone::forState($this->state);

        if (! $zone) {
            return null;
        }

        $basket = (int) round(((float) $this->basket) * 100);

        return ['zone' => $zone, 'fee' => $zone->feeFor($basket)];
    }
}; ?>

<div>
    <section class="bg-gradient-to-br from-brand-50 via-white to-amber-50 dark:from-brand-950 dark:via-zinc-950 dark:to-zinc-900">
        <div class="container-shop max-w-4xl py-14 text-center">
            <p class="text-sm font-bold tracking-widest text-amber-600 uppercase dark:text-amber-400">Good to know</p>
            <h1 class="mt-2 text-4xl font-extrabold tracking-tight text-brand-950 sm:text-5xl dark:text-white">Delivery & returns</h1>
        </div>
    </section>

    <div class="container-shop max-w-4xl space-y-12 pt-12">
        <section class="rounded-3xl border-2 border-brand-200 bg-brand-50/50 p-6 sm:p-8 dark:border-brand-900 dark:bg-brand-950/30" aria-labelledby="calc-title" data-reveal>
            <h2 id="calc-title" class="text-xl font-bold">How much is delivery to me?</h2>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <flux:select wire:model.live="state" label="Your state">
                    <flux:select.option value="">Choose your state</flux:select.option>
                    @foreach (\App\Models\DeliveryZone::STATES as $option)
                        <flux:select.option :value="$option">{{ $option }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model.live.debounce.400ms="basket" type="number" min="0" step="1000" label="Order amount (₦, optional)" placeholder="e.g. 45000" />
            </div>
            @if ($state !== '')
                <div class="mt-5 rounded-2xl bg-white p-5 dark:bg-zinc-900" wire:transition>
                    @if ($quote = $this->quote)
                        <p class="text-lg">Delivery to <strong>{{ $state }}</strong>:
                            <span class="font-extrabold text-brand-700 dark:text-brand-400">{{ $quote['fee'] > 0 ? naira($quote['fee']) : 'FREE' }}</span>
                            · {{ $quote['zone']->eta }}</p>
                        @if ($quote['zone']->free_over && $quote['fee'] > 0)
                            <p class="mt-1 text-sm text-zinc-500">Free on orders of {{ naira($quote['zone']->free_over) }} or more.</p>
                        @endif
                    @else
                        <p>We don't deliver to {{ $state }} yet. <a href="{{ route('contact') }}" wire:navigate.hover class="font-semibold text-brand-700 underline">Contact us</a> and we'll arrange it.</p>
                    @endif
                </div>
            @endif
        </section>

        <section data-reveal>
            <h2 class="text-2xl font-bold">Delivery fees</h2>
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">We deliver to all 36 states and Abuja. Delivery times start once your payment is confirmed.</p>
            <div class="mt-5 overflow-x-auto rounded-3xl border">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 text-xs tracking-wider text-zinc-500 uppercase dark:bg-zinc-900">
                        <tr><th class="px-5 py-3 text-start">Zone</th><th class="px-5 py-3 text-start">Fee</th><th class="px-5 py-3 text-start">Time</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($this->zones as $zone)
                            <tr wire:key="zone-{{ $zone->id }}" @class(['bg-brand-50/60 dark:bg-brand-950/30' => $this->quote && $this->quote['zone']->is($zone)])>
                                <td class="px-5 py-4"><p class="font-semibold">{{ $zone->name }}</p><p class="text-xs text-zinc-500">{{ implode(', ', $zone->states) }}</p></td>
                                <td class="px-5 py-4 font-semibold">{{ naira($zone->fee) }}@if ($zone->free_over)<br><span class="text-xs font-normal text-brand-700 dark:text-brand-400">Free over {{ naira($zone->free_over) }}</span>@endif</td>
                                <td class="px-5 py-4">{{ $zone->eta }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section data-reveal class="prose-shop text-zinc-600 dark:text-zinc-400">
            <h2 class="mb-3 text-2xl font-bold text-zinc-900 dark:text-white">Returns & exchanges</h2>
            <ul>
                <li>You can return unused items in their original packaging within <strong>7 days</strong> of delivery.</li>
                <li>For hygiene reasons, opened skincare, makeup, fragrance and worn wigs can only be returned if they arrived damaged or were the wrong item.</li>
                <li>Message us on WhatsApp or through the <a href="{{ route('contact') }}" wire:navigate.hover class="font-semibold text-brand-700 underline dark:text-brand-400">contact page</a> with your order number and photos, and we'll arrange the return.</li>
                <li>Refunds go back to the card or bank account you paid with within 5 working days of us receiving the item.</li>
            </ul>
        </section>
    </div>
</div>
