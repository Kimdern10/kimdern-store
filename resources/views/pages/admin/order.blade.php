<?php

use App\Livewire\Concerns\AdminOnly;
use App\Actions\UpdateOrderStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin'), Title('Order')] class extends Component {
    use AdminOnly;

    public Order $order;

    public string $status = '';

    public string $note = '';

    public string $trackingNumber = '';

    public function mount(Order $order): void
    {
        $this->order = $order->load(['items.product', 'history', 'user']);
        $this->status = $this->nextStatus()?->value ?? $order->status->value;
        $this->trackingNumber = (string) $order->tracking_number;
    }

    /**
     * The step that usually comes next, to pre-select in the form.
     */
    public function nextStatus(): ?OrderStatus
    {
        $timeline = OrderStatus::timeline();

        return $timeline[$this->order->status->step() + 1] ?? null;
    }

    public function updateStatus(UpdateOrderStatus $updateOrderStatus): void
    {
        $this->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'note' => ['nullable', 'string', 'max:255'],
            'trackingNumber' => ['nullable', 'string', 'max:100'],
        ]);

        $updateOrderStatus->handle($this->order, OrderStatus::from($this->status), $this->note ?: null, $this->trackingNumber ?: null);

        $this->order->refresh()->load(['items.product', 'history', 'user']);
        $this->reset('note');
        $this->status = $this->nextStatus()?->value ?? $this->order->status->value;

        Flux::toast(variant: 'success', text: 'Order updated and the customer has been emailed.');
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.orders') }}" class="text-sm font-semibold text-brand-700 hover:underline dark:text-brand-400">← Orders</a>
            <div class="mt-1 flex items-center gap-3">
                <flux:heading size="xl">{{ $order->number }}</flux:heading>
                <flux:badge :color="$order->status->colour()">{{ $order->status->label() }}</flux:badge>
            </div>
            <flux:text>Placed {{ $order->created_at->format('l j F Y, g:i a') }}</flux:text>
        </div>
        <div class="flex gap-2">
            <flux:button icon="document-text" :href="$order->receiptUrl()" target="_blank">Receipt</flux:button>
            <flux:button icon="eye" :href="$order->trackingUrl()" target="_blank">Customer view</flux:button>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($order->items as $item)
                        <li class="flex items-center gap-4 p-4">
                            <img src="{{ $item->imageUrl() }}" alt="" class="size-14 rounded-xl object-cover">
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold">{{ $item->name }}</p>
                                <p class="text-sm text-zinc-500">{{ $item->variant_label ? $item->variant_label.' · ' : '' }}{{ $item->quantity }} × {{ naira($item->price) }}</p>
                            </div>
                            <p class="font-semibold">{{ naira($item->line_total) }}</p>
                        </li>
                    @endforeach
                </ul>
                <dl class="space-y-1.5 border-t border-zinc-100 p-4 text-sm dark:border-zinc-800">
                    <div class="flex justify-between"><dt>Subtotal</dt><dd>{{ naira($order->subtotal) }}</dd></div>
                    @if ($order->discount > 0)<div class="flex justify-between"><dt>Discount ({{ $order->coupon_code }})</dt><dd>−{{ naira($order->discount) }}</dd></div>@endif
                    <div class="flex justify-between"><dt>Delivery</dt><dd>{{ naira($order->delivery_fee) }}</dd></div>
                    <div class="flex justify-between pt-2 text-base font-bold"><dt>Total</dt><dd>{{ naira($order->total) }}</dd></div>
                </dl>
            </section>

            <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading>History</flux:heading>
                <ol class="mt-4 space-y-4 border-s-2 border-brand-100 ps-5 dark:border-brand-900">
                    @foreach ($order->history->reverse() as $entry)
                        <li class="relative">
                            <span class="absolute -start-[1.6rem] top-1 size-3 rounded-full bg-brand-600 ring-4 ring-white dark:ring-zinc-900"></span>
                            <p class="font-semibold">{{ $entry->status->label() }} <span class="text-sm font-normal text-zinc-500">· {{ $entry->created_at->format('j M Y, g:i a') }}</span></p>
                            @if ($entry->note)<p class="text-sm text-zinc-500">{{ $entry->note }}</p>@endif
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>

        <div class="space-y-6">
            <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading>Update status</flux:heading>
                @if ($order->status === OrderStatus::Cancelled)
                    <flux:text class="mt-2">This order is cancelled and can't be changed.</flux:text>
                @else
                    <form wire:submit="updateStatus" class="mt-4 space-y-4">
                        <flux:select wire:model.live="status" label="New status">
                            @foreach (OrderStatus::cases() as $case)
                                <flux:select.option :value="$case->value" :disabled="$case === $order->status">{{ $case->label() }}{{ $case === $order->status ? ' (now)' : '' }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        @if ($status === OrderStatus::Shipped->value)
                            <flux:input wire:model="trackingNumber" label="Tracking / waybill number (optional)" />
                        @endif
                        <flux:textarea wire:model="note" label="Note to the customer (optional)" rows="2" placeholder="e.g. Your rider will call before delivery." />
                        @if ($status === OrderStatus::Paid->value && ! $order->paid_at)
                            <flux:callout variant="warning" icon="banknotes" text="Only mark as paid once you've seen {{ naira($order->total) }} in the bank account." />
                        @endif
                        @if ($status === OrderStatus::Cancelled->value)
                            <flux:callout variant="danger" icon="exclamation-triangle" text="Cancelling puts the items back in stock. Refund any payment yourself." />
                        @endif
                        @error('status')<flux:text class="text-red-600">{{ $message }}</flux:text>@enderror
                        <flux:button type="submit" variant="primary" class="w-full">Save and email customer</flux:button>
                    </form>
                @endif
            </section>

            <section class="space-y-3 rounded-2xl border border-zinc-200 bg-white p-5 text-sm dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading>Customer</flux:heading>
                <p class="font-semibold">{{ $order->name }} @if ($order->user)<flux:badge size="sm">Has account</flux:badge>@else<flux:badge size="sm" color="zinc">Guest</flux:badge>@endif</p>
                <p><a href="mailto:{{ $order->email }}" class="text-brand-700 hover:underline dark:text-brand-400">{{ $order->email }}</a></p>
                <p><a href="tel:{{ $order->phone }}" class="text-brand-700 hover:underline dark:text-brand-400">{{ $order->phone }}</a>
                    · <a href="https://wa.me/{{ preg_replace(['/\D+/', '/^0/'], ['', '234'], $order->phone) }}" target="_blank" rel="noopener" class="text-brand-700 hover:underline dark:text-brand-400">WhatsApp</a></p>
                <flux:separator />
                <flux:heading>Deliver to</flux:heading>
                <p>{{ $order->fullAddress() }}</p>
                @if ($order->notes)<p class="rounded-xl bg-amber-50 p-3 text-amber-900 dark:bg-amber-950/40 dark:text-amber-200">“{{ $order->notes }}”</p>@endif
                <flux:separator />
                <flux:heading>Payment</flux:heading>
                <p>{{ $order->paymentMethodLabel() }}</p>
                <p class="text-zinc-500">{{ $order->paid_at ? 'Paid '.$order->paid_at->format('j M Y, g:i a') : 'Not paid yet' }}</p>
                @if ($order->payment_reference)<p class="font-mono text-xs text-zinc-500">{{ $order->payment_reference }}</p>@endif
            </section>
        </div>
    </div>
</div>
