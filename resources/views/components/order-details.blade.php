@props(['order'])
@php
    use App\Enums\OrderStatus;
    $store = \App\Models\Setting::values();
    $current = $order->status->step();
@endphp
<div {{ $attributes->class('space-y-8') }}>
    {{-- Timeline --}}
    <section class="rounded-3xl border p-6" aria-label="Order progress">
        @if ($order->status === OrderStatus::Cancelled)
            <div class="flex items-center gap-3 text-red-600"><flux:icon.x-circle class="size-7" /><p class="font-bold">This order was cancelled.</p></div>
        @else
            <ol class="grid grid-cols-5 gap-1">
                @foreach (OrderStatus::timeline() as $i => $step)
                    @php($done = $i <= $current)
                    <li class="relative flex flex-col items-center text-center">
                        @if ($i > 0)
                            <span class="absolute end-1/2 top-4 h-1 w-full -translate-y-1/2 rounded-full {{ $done ? 'bg-brand-600' : 'bg-zinc-200 dark:bg-zinc-800' }}" aria-hidden="true"></span>
                        @endif
                        <span @class(['relative grid size-8 place-items-center rounded-full text-xs font-bold ring-4 ring-white dark:ring-zinc-950', 'bg-brand-600 text-white' => $done, 'bg-zinc-200 text-zinc-500 dark:bg-zinc-800' => ! $done, 'animate-pulse' => $i === $current && $step !== OrderStatus::Delivered])>
                            @if ($done)
                                <flux:icon.check variant="micro" />
                            @else
                                {{ $i + 1 }}
                            @endif
                        </span>
                        <span class="mt-2 text-[11px] leading-tight font-semibold sm:text-sm {{ $done ? 'text-brand-800 dark:text-brand-300' : 'text-zinc-500' }}">{{ $step === OrderStatus::Pending ? 'Ordered' : $step->label() }}</span>
                    </li>
                @endforeach
            </ol>
        @endif
        <p class="mt-6 text-center text-zinc-600 dark:text-zinc-400">{{ $order->status->description() }}
            @if ($order->tracking_number)
                Tracking number: <strong>{{ $order->tracking_number }}</strong>.
            @endif
        </p>

        @if ($order->history->isNotEmpty())
            <details class="mt-4 text-sm">
                <summary class="cursor-pointer text-center font-semibold text-brand-700 dark:text-brand-400">See full history</summary>
                <ul class="mx-auto mt-4 max-w-md space-y-3">
                    @foreach ($order->history->reverse() as $entry)
                        <li class="flex gap-3">
                            <span class="mt-1.5 size-2 shrink-0 rounded-full bg-brand-500"></span>
                            <div><p class="font-semibold">{{ $entry->status->label() }} <span class="font-normal text-zinc-500">· {{ $entry->created_at->format('j M Y, g:i a') }}</span></p>@if ($entry->note)<p class="text-zinc-500">{{ $entry->note }}</p>@endif</div>
                        </li>
                    @endforeach
                </ul>
            </details>
        @endif
    </section>

    {{-- Bank details while waiting for a transfer --}}
    @if ($order->awaitingTransferCheck())
        <section class="flex items-start gap-4 rounded-3xl border-2 border-brand-300 bg-brand-50 p-6 dark:border-brand-800 dark:bg-brand-950/40" aria-live="polite">
            <span class="relative mt-1 flex size-4 shrink-0"><span class="absolute inline-flex size-full animate-ping rounded-full bg-brand-400 opacity-75"></span><span class="relative inline-flex size-4 rounded-full bg-brand-600"></span></span>
            <div>
                <h2 class="text-lg font-bold">We're confirming your payment</h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">You told us you sent {{ naira($order->total) }} at {{ $order->transfer_reported_at->format('g:i a') }}. As soon as we see it in our account, this page updates by itself and we email your receipt. There's nothing else you need to do.</p>
            </div>
        </section>
    @elseif ($order->status === OrderStatus::Pending && $order->payment_method === 'bank_transfer')
        <section class="rounded-3xl border-2 border-amber-300 bg-amber-50 p-6 dark:border-amber-700 dark:bg-amber-950/30" aria-labelledby="pay-title">
            <h2 id="pay-title" class="text-lg font-bold">Complete your payment by bank transfer</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Send exactly <strong>{{ naira($order->total) }}</strong> from any bank app (OPay, Moniepoint, Kuda, PalmPay or your bank) and use <strong>{{ $order->number }}</strong> as the narration. Then tap the button below.</p>
            <dl class="mt-4 grid gap-3 sm:grid-cols-3" x-data>
                @foreach (['Bank' => $store['bank_name'], 'Account name' => $store['bank_account_name'], 'Account number' => $store['bank_account_number']] as $label => $value)
                    <div class="rounded-2xl bg-white p-4 dark:bg-zinc-900">
                        <dt class="text-xs text-zinc-500">{{ $label }}</dt>
                        <dd class="mt-1 flex items-center justify-between gap-2 font-bold">{{ $value }}
                            @if ($label === 'Account number')
                                <button type="button" x-on:click="navigator.clipboard.writeText('{{ $value }}'); $el.textContent = 'Copied'" class="text-xs font-semibold text-brand-700 dark:text-brand-400">Copy</button>
                            @endif
                        </dd>
                    </div>
                @endforeach
            </dl>
            <button type="button" wire:click="reportTransfer" wire:loading.attr="disabled" class="mt-5 flex h-13 w-full items-center justify-center gap-2 rounded-full bg-brand-700 px-6 font-bold text-white shadow-lg shadow-brand-700/25 transition hover:-translate-y-0.5 hover:bg-brand-800 disabled:opacity-70 sm:w-auto">
                <flux:icon.check-circle variant="micro" wire:loading.remove wire:target="reportTransfer" />
                <span wire:loading wire:target="reportTransfer" class="size-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                I have sent the money
            </button>
        </section>
    @endif

    <div class="grid gap-8 lg:grid-cols-[1fr_20rem]">
        <section class="rounded-3xl border" aria-label="Items">
            <ul class="divide-y">
                @foreach ($order->items as $item)
                    <li class="flex items-center gap-4 p-5">
                        <img src="{{ $item->imageUrl() }}" alt="" class="size-16 rounded-xl object-cover">
                        <div class="min-w-0 flex-1">
                            @if ($item->product)
                                <a href="{{ route('product', $item->product) }}" wire:navigate class="font-semibold hover:text-brand-700">{{ $item->name }}</a>
                            @else
                                <p class="font-semibold">{{ $item->name }}</p>
                            @endif
                            <p class="text-sm text-zinc-500">{{ $item->variant_label ? $item->variant_label.' · ' : '' }}{{ $item->quantity }} × {{ naira($item->price) }}</p>
                        </div>
                        <p class="font-bold">{{ naira($item->line_total) }}</p>
                    </li>
                @endforeach
            </ul>
            <dl class="space-y-2 border-t p-5 text-sm">
                <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ naira($order->subtotal) }}</dd></div>
                @if ($order->discount > 0)
                    <div class="flex justify-between text-brand-700 dark:text-brand-400"><dt>Discount ({{ $order->coupon_code }})</dt><dd>−{{ naira($order->discount) }}</dd></div>
                @endif
                <div class="flex justify-between"><dt>Delivery</dt><dd>{{ $order->delivery_fee > 0 ? naira($order->delivery_fee) : 'Free' }}</dd></div>
                <div class="flex justify-between border-t pt-3 text-lg font-extrabold"><dt>Total</dt><dd>{{ naira($order->total) }}</dd></div>
            </dl>
        </section>

        <aside class="space-y-4 text-sm">
            <div class="rounded-3xl border p-5">
                <h3 class="font-bold">Delivery</h3>
                <p class="mt-2">{{ $order->name }}<br>{{ $order->fullAddress() }}<br>{{ $order->phone }}</p>
                @if ($order->delivery_eta)<p class="mt-2 text-zinc-500">Expected: {{ $order->delivery_eta }} after payment</p>@endif
            </div>
            <div class="rounded-3xl border p-5">
                <h3 class="font-bold">Payment</h3>
                <p class="mt-2">{{ $order->paymentMethodLabel() }}</p>
                <p class="text-zinc-500">{{ $order->paid_at ? 'Paid on '.$order->paid_at->format('j M Y') : 'Not paid yet' }}</p>
            </div>
            <flux:button href="{{ $order->receiptUrl() }}" icon="arrow-down-tray" class="w-full">Download receipt (PDF)</flux:button>
        </aside>
    </div>
</div>
