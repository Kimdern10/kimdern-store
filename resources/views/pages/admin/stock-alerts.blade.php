<?php

use App\Livewire\Concerns\AdminOnly;
use App\Models\StockAlert;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Which sold-out items shoppers are waiting for, so the owner knows what to restock first.
 */
new #[Layout('layouts::admin'), Title('Back in stock requests')] class extends Component {
    use AdminOnly;

    /**
     * @return Collection<int, array{product_id: int, name: string, option: string|null, waiting: int, latest: string}>
     */
    #[Computed]
    public function waiting(): Collection
    {
        return StockAlert::query()->with(['product:id,name', 'variant'])
            ->whereNull('notified_at')
            ->latest()
            ->get()
            ->filter(fn (StockAlert $a) => $a->product !== null)
            ->groupBy(fn (StockAlert $a) => $a->product_id.':'.($a->product_variant_id ?? 0))
            ->map(fn ($alerts) => [
                'product_id' => $alerts->first()->product_id,
                'name' => $alerts->first()->product->name,
                'option' => $alerts->first()->variant?->label(),
                'waiting' => $alerts->count(),
                'latest' => $alerts->first()->created_at->diffForHumans(),
            ])
            ->sortByDesc('waiting')
            ->values();
    }

    #[Computed]
    public function notifiedCount(): int
    {
        return StockAlert::query()->whereNotNull('notified_at')->count();
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl">Back in stock requests</flux:heading>
        <flux:text>Shoppers who asked to be emailed when a sold-out item returns. When you add stock to the product, they are emailed automatically. {{ $this->notifiedCount }} {{ \Illuminate\Support\Str::plural('email', $this->notifiedCount) }} sent so far.</flux:text>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead class="border-b border-zinc-100 bg-zinc-50 text-xs tracking-wide text-zinc-500 uppercase dark:border-zinc-800 dark:bg-zinc-950/40">
                <tr><th class="px-5 py-3 text-start">Product</th><th class="px-5 py-3 text-start">People waiting</th><th class="px-5 py-3 text-start">Last request</th><th class="px-5 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->waiting as $row)
                    <tr>
                        <td class="px-5 py-3"><p class="font-semibold">{{ $row['name'] }}</p>@if ($row['option'])<p class="text-xs text-zinc-500">{{ $row['option'] }}</p>@endif</td>
                        <td class="px-5 py-3"><flux:badge color="amber">{{ $row['waiting'] }}</flux:badge></td>
                        <td class="px-5 py-3 text-zinc-500">{{ $row['latest'] }}</td>
                        <td class="px-5 py-3 text-end"><flux:button size="sm" :href="route('admin.products.edit', $row['product_id'])" wire:navigate.hover>Restock</flux:button></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-12 text-center text-zinc-500">Nobody is waiting for anything right now.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
