<?php

use App\Livewire\Concerns\AdminOnly;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin'), Title('Orders')] class extends Component {
    use AdminOnly, WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return Order::query()->withCount('items')
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(fn ($q) => $q->where('number', 'like', $term)->orWhere('name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('phone', 'like', $term));
            })
            ->latest()
            ->paginate(20);
    }

    /** @return array<string, int> */
    #[Computed]
    public function counts(): array
    {
        return Order::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->map(fn ($n) => (int) $n)->all();
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">Orders</flux:heading>
        <flux:button icon="arrow-down-tray" :href="route('admin.orders.export', array_filter(['status' => $status, 'search' => $search]))">Export to Excel (CSV)</flux:button>
    </div>

    <div class="no-scrollbar flex gap-2 overflow-x-auto">
        <button type="button" wire:click="$set('status', '')" @class(['shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition', 'bg-brand-700 text-white' => $status === '', 'bg-white ring-1 ring-zinc-200 hover:bg-zinc-100 dark:bg-zinc-900 dark:ring-zinc-800' => $status !== ''])>All <span class="opacity-70">{{ array_sum($this->counts) }}</span></button>
        @foreach (OrderStatus::cases() as $case)
            <button type="button" wire:click="$set('status', '{{ $case->value }}')" @class(['shrink-0 rounded-full px-4 py-2 text-sm font-semibold transition', 'bg-brand-700 text-white' => $status === $case->value, 'bg-white ring-1 ring-zinc-200 hover:bg-zinc-100 dark:bg-zinc-900 dark:ring-zinc-800' => $status !== $case->value])>{{ $case->label() }} <span class="opacity-70">{{ $this->counts[$case->value] ?? 0 }}</span></button>
        @endforeach
    </div>

    <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass" placeholder="Search by order number, name, email or phone" class="max-w-md" />

    <div class="overflow-x-auto rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead class="border-b border-zinc-100 bg-zinc-50 text-xs tracking-wide text-zinc-500 uppercase dark:border-zinc-800 dark:bg-zinc-950/40">
                <tr>
                    <th class="px-5 py-3 text-start">Order</th>
                    <th class="px-5 py-3 text-start">Customer</th>
                    <th class="px-5 py-3 text-start">Payment</th>
                    <th class="px-5 py-3 text-start">Status</th>
                    <th class="px-5 py-3 text-end">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->orders as $order)
                    <tr wire:key="order-{{ $order->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-brand-700 hover:underline dark:text-brand-400" wire:navigate.hover>{{ $order->number }}</a>
                            <p class="text-xs text-zinc-500">{{ $order->created_at->format('j M Y, g:i a') }} · {{ $order->items_count }} {{ \Illuminate\Support\Str::plural('line', $order->items_count) }}</p>
                        </td>
                        <td class="px-5 py-3">{{ $order->name }}<p class="text-xs text-zinc-500">{{ $order->phone }} · {{ $order->state }}</p></td>
                        <td class="px-5 py-3">{{ $order->payment_method === 'paystack' ? 'Paystack' : 'Transfer' }}<p class="text-xs {{ $order->paid_at ? 'text-brand-600' : 'text-amber-600' }}">{{ $order->paid_at ? 'Paid' : ($order->transfer_reported_at ? 'Customer says sent: check bank' : 'Not paid') }}</p></td>
                        <td class="px-5 py-3"><flux:badge size="sm" :color="$order->status->colour()">{{ $order->status->label() }}</flux:badge></td>
                        <td class="px-5 py-3 text-end font-semibold">{{ naira($order->total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-zinc-500">No orders found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->orders->links() }}
</div>
