<?php

use App\Livewire\Concerns\AdminOnly;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin'), Title('Dashboard')] class extends Component {
    use AdminOnly;

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function stats(): array
    {
        $paid = Order::query()->whereNotNull('paid_at')->where('status', '!=', OrderStatus::Cancelled);

        return [
            'revenue_month' => (int) (clone $paid)->where('paid_at', '>=', now()->startOfMonth())->sum('total'),
            'revenue_last_month' => (int) (clone $paid)->whereBetween('paid_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])->sum('total'),
            'orders_today' => Order::query()->where('created_at', '>=', now()->startOfDay())->count(),
            'to_ship' => Order::query()->whereIn('status', [OrderStatus::Paid, OrderStatus::Processing])->count(),
            'awaiting_payment' => Order::query()->where('status', OrderStatus::Pending)->count(),
            'customers' => User::query()->where('is_admin', false)->count(),
            'average_order' => (int) (clone $paid)->avg('total'),
        ];
    }

    /**
     * Paid sales per day for the last 14 days.
     *
     * @return SupportCollection<int, array{label: string, total: int}>
     */
    #[Computed]
    public function sales(): SupportCollection
    {
        $totals = Order::query()->whereNotNull('paid_at')->where('status', '!=', OrderStatus::Cancelled)
            ->where('paid_at', '>=', now()->subDays(13)->startOfDay())
            ->get(['paid_at', 'total'])
            ->groupBy(fn (Order $o) => $o->paid_at->format('Y-m-d'))
            ->map(fn ($day) => (int) $day->sum('total'));

        return collect(range(13, 0))->map(function (int $daysAgo) use ($totals) {
            $date = now()->subDays($daysAgo);

            return ['label' => $date->format('j M'), 'total' => $totals[$date->format('Y-m-d')] ?? 0];
        });
    }

    /** @return Collection<int, Order> */
    #[Computed]
    public function recentOrders(): Collection
    {
        return Order::query()->latest()->limit(6)->get();
    }

    /**
     * Products (or sizes/colours) running low.
     *
     * @return SupportCollection<int, array{name: string, detail: string|null, stock: int, product_id: int}>
     */
    #[Computed]
    public function lowStock(): SupportCollection
    {
        $limit = (int) Setting::get('low_stock_threshold', '5');

        $simple = Product::query()->active()->doesntHave('variants')->where('stock', '<=', $limit)->orderBy('stock')->limit(10)->get()
            ->map(fn (Product $p) => ['name' => $p->name, 'detail' => null, 'stock' => $p->stock, 'product_id' => $p->id]);

        $variants = ProductVariant::query()->with('product:id,name,is_active')->where('stock', '<=', $limit)->orderBy('stock')->limit(10)->get()
            ->filter(fn (ProductVariant $v) => $v->product?->is_active)
            ->map(fn (ProductVariant $v) => ['name' => $v->product->name, 'detail' => $v->label(), 'stock' => $v->stock, 'product_id' => $v->product_id]);

        return $simple->concat($variants)->sortBy('stock')->take(10)->values();
    }
}; ?>

<div class="space-y-8">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }}</flux:heading>
            <flux:text>Here's how {{ \App\Models\Setting::get('store_name') }} is doing.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" :href="route('admin.products.create')" wire:navigate.hover>Add product</flux:button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $change = $this->stats['revenue_last_month'] > 0 ? round(($this->stats['revenue_month'] - $this->stats['revenue_last_month']) / $this->stats['revenue_last_month'] * 100) : null;
        @endphp
        <div class="rounded-2xl bg-gradient-to-br from-brand-700 to-brand-900 p-5 text-white shadow-lg shadow-brand-900/20">
            <p class="text-sm text-white/75">Sales this month</p>
            <p class="mt-2 text-3xl font-extrabold">{{ naira($this->stats['revenue_month']) }}</p>
            <p class="mt-1 text-xs text-white/70">
                @if ($change !== null)
                    {{ $change >= 0 ? '▲' : '▼' }} {{ abs($change) }}% vs last month
                @else
                    Average order {{ naira($this->stats['average_order']) }}
                @endif
            </p>
        </div>
        @foreach ([
            ['To pack & ship', $this->stats['to_ship'], 'truck', route('admin.orders', ['status' => 'paid']), 'Paid orders waiting'],
            ['Awaiting payment', $this->stats['awaiting_payment'], 'clock', route('admin.orders', ['status' => 'pending']), 'Check bank transfers'],
            ['Orders today', $this->stats['orders_today'], 'shopping-bag', route('admin.orders'), $this->stats['customers'].' customer accounts'],
        ] as [$label, $value, $icon, $link, $note])
            <a href="{{ $link }}" class="rounded-2xl border border-zinc-200 bg-white p-5 transition hover:-translate-y-0.5 hover:shadow-lg dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-zinc-500">{{ $label }}</p>
                    <flux:icon :name="$icon" class="size-5 text-brand-600" />
                </div>
                <p class="mt-2 text-3xl font-extrabold">{{ $value }}</p>
                <p class="mt-1 text-xs text-zinc-500">{{ $note }}</p>
            </a>
        @endforeach
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <section class="rounded-2xl border border-zinc-200 bg-white p-5 xl:col-span-2 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:heading>Sales, last 14 days</flux:heading>
                <flux:text>{{ naira($this->sales->sum('total')) }} total</flux:text>
            </div>
            @php($peak = max(1, $this->sales->max('total')))
            <div class="mt-6 flex h-56 items-end gap-1.5 sm:gap-2" role="img" aria-label="Bar chart of daily sales">
                @foreach ($this->sales as $day)
                    <div class="group relative flex h-full flex-1 flex-col justify-end">
                        <div class="w-full rounded-t-lg bg-gradient-to-t from-brand-700 to-brand-400 transition-all duration-700 group-hover:from-amber-500 group-hover:to-amber-300" style="height: {{ max(2, $day['total'] / $peak * 100) }}%"></div>
                        <span class="pointer-events-none absolute -top-8 left-1/2 z-10 -translate-x-1/2 rounded-md bg-zinc-900 px-2 py-1 text-xs whitespace-nowrap text-white opacity-0 transition group-hover:opacity-100">{{ naira($day['total']) }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex gap-1.5 text-[10px] text-zinc-400 sm:gap-2">
                @foreach ($this->sales as $i => $day)
                    <span class="flex-1 text-center">{{ $i % 2 === 0 ? $day['label'] : '' }}</span>
                @endforeach
            </div>
        </section>

        <section class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between">
                <flux:heading>Low stock</flux:heading>
                <flux:badge size="sm" color="amber">{{ $this->lowStock->count() }}</flux:badge>
            </div>
            <ul class="mt-4 divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->lowStock as $row)
                    <li class="flex items-center justify-between gap-3 py-2.5 text-sm">
                        <a href="{{ route('admin.products.edit', $row['product_id']) }}" class="min-w-0 hover:underline" wire:navigate.hover>
                            <p class="truncate font-medium">{{ $row['name'] }}</p>
                            @if ($row['detail'])<p class="text-xs text-zinc-500">{{ $row['detail'] }}</p>@endif
                        </a>
                        <flux:badge size="sm" :color="$row['stock'] === 0 ? 'red' : 'amber'">{{ $row['stock'] === 0 ? 'Sold out' : $row['stock'].' left' }}</flux:badge>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-zinc-500">Everything is well stocked 🎉</li>
                @endforelse
            </ul>
        </section>
    </div>

    <section class="rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-center justify-between p-5">
            <flux:heading>Recent orders</flux:heading>
            <flux:button size="sm" variant="ghost" :href="route('admin.orders')" wire:navigate.hover>See all</flux:button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="border-y border-zinc-100 bg-zinc-50 text-start text-xs tracking-wide text-zinc-500 uppercase dark:border-zinc-800 dark:bg-zinc-950/40">
                    <tr><th class="px-5 py-3 text-start">Order</th><th class="px-5 py-3 text-start">Customer</th><th class="px-5 py-3 text-start">Status</th><th class="px-5 py-3 text-end">Total</th></tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($this->recentOrders as $order)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-5 py-3"><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-brand-700 hover:underline dark:text-brand-400" wire:navigate.hover>{{ $order->number }}</a><p class="text-xs text-zinc-500">{{ $order->created_at->diffForHumans() }}</p></td>
                            <td class="px-5 py-3">{{ $order->name }}<p class="text-xs text-zinc-500">{{ $order->state }}</p></td>
                            <td class="px-5 py-3"><flux:badge size="sm" :color="$order->status->colour()">{{ $order->status->label() }}</flux:badge></td>
                            <td class="px-5 py-3 text-end font-semibold">{{ naira($order->total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
