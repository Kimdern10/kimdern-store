<?php

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Live search in the header: results appear while you type.
 */
new class extends Component {
    public string $query = '';

    /**
     * @return Collection<int, Product>
     */
    #[Computed]
    public function results(): Collection
    {
        $term = trim($this->query);

        if (mb_strlen($term) < 2) {
            return new Collection;
        }

        return Product::query()->active()->with('mainImage')
            ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('brand', 'like', "%{$term}%")->orWhere('short_description', 'like', "%{$term}%"))
            ->orderByDesc('sales_count')
            ->limit(6)
            ->get();
    }
}; ?>

<div x-data="{ open: false }" x-on:keydown.escape.window="open = false" class="relative">
    <button type="button" x-on:click="open = true; $nextTick(() => $refs.input.focus())" class="grid size-10 place-items-center rounded-full transition hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Search">
        <flux:icon.magnifying-glass variant="outline" class="size-5" />
    </button>

    <template x-teleport="body">
        <div x-cloak x-show="open" class="fixed inset-0 z-50" role="dialog" aria-modal="true" aria-label="Search products">
            <div x-show="open" x-transition.opacity class="absolute inset-0 bg-zinc-950/50 backdrop-blur-sm" x-on:click="open = false"></div>
            <div x-show="open" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="-translate-y-6 opacity-0" class="relative mx-auto mt-4 w-[calc(100%-2rem)] max-w-2xl overflow-hidden rounded-3xl bg-white shadow-2xl sm:mt-20 dark:bg-zinc-900">
                <form action="{{ route('shop') }}" method="get" class="flex items-center gap-3 border-b px-5">
                    <flux:icon.magnifying-glass class="size-5 text-zinc-400" />
                    <input x-ref="input" name="q" type="search" wire:model.live.debounce.300ms="query" placeholder="Search serums, perfume, sneakers..." autocomplete="off" class="h-16 flex-1 bg-transparent text-base outline-none placeholder:text-zinc-400" aria-label="Search products">
                    <div wire:loading class="size-5 animate-spin rounded-full border-2 border-brand-600 border-t-transparent"></div>
                    <button type="button" x-on:click="open = false" class="rounded-lg border px-2 py-1 text-xs text-zinc-500">Esc</button>
                </form>

                @if (mb_strlen(trim($query)) >= 2)
                    @if ($this->results->isEmpty())
                        <p class="p-8 text-center text-sm text-zinc-500">No products match “{{ $query }}”. Try another word.</p>
                    @else
                        <ul class="max-h-[60vh] divide-y overflow-y-auto">
                            @foreach ($this->results as $product)
                                <li wire:key="search-{{ $product->id }}">
                                    <a href="{{ route('product', $product) }}" class="flex items-center gap-4 px-5 py-3 transition hover:bg-brand-50 dark:hover:bg-brand-950/40">
                                        <img src="{{ $product->imageUrl() }}" alt="" class="size-14 rounded-xl object-cover">
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate font-semibold">{{ $product->name }}</p>
                                            <p class="text-xs text-zinc-500">{{ $product->brand }}</p>
                                        </div>
                                        <p class="font-bold">{{ naira($product->price) }}</p>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                        <a href="{{ route('shop', ['q' => $query]) }}" class="block border-t px-5 py-4 text-center text-sm font-semibold text-brand-700 hover:bg-zinc-50 dark:text-brand-400 dark:hover:bg-zinc-800">See all results for “{{ $query }}” →</a>
                    @endif
                @else
                    <div class="flex flex-wrap gap-2 p-5">
                        <span class="w-full text-xs font-bold tracking-widest text-zinc-400 uppercase">Popular</span>
                        @foreach (['Serum', 'Lipstick', 'Perfume', 'Dress', 'Sneakers', 'Bag'] as $word)
                            <button type="button" wire:click="$set('query', '{{ $word }}')" class="rounded-full border px-3 py-1.5 text-sm transition hover:border-brand-600 hover:text-brand-700 dark:hover:text-brand-400">{{ $word }}</button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </template>
</div>
