<div class="space-y-10">
    <section data-reveal>
        <h2 class="text-2xl font-bold">Delivery fees</h2>
        <p class="mt-2 text-zinc-600 dark:text-zinc-400">We deliver to all 36 states and Abuja. Delivery times start once your payment is confirmed.</p>
        <div class="mt-5 overflow-hidden rounded-3xl border">
            <table class="w-full text-start text-sm">
                <thead class="bg-zinc-50 text-xs tracking-wider text-zinc-500 uppercase dark:bg-zinc-900">
                    <tr><th class="px-5 py-3 text-start">Zone</th><th class="px-5 py-3 text-start">Fee</th><th class="px-5 py-3 text-start">Time</th></tr>
                </thead>
                <tbody class="divide-y">
                    @foreach (\App\Models\DeliveryZone::query()->where('is_active', true)->orderBy('sort_order')->get() as $zone)
                        <tr>
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
            <li>For hygiene reasons, opened skincare, makeup and fragrance can only be returned if they arrived damaged or were the wrong item.</li>
            <li>Message us on WhatsApp or email with your order number and photos, and we'll arrange the return.</li>
            <li>Refunds go back to the card or bank account you paid with within 5 working days of us receiving the item.</li>
        </ul>
    </section>
</div>
