@props(['product', 'wishlisted' => false, 'delay' => 0])
@php
    $hasVariants = $product->hasVariants();
    $inStock = $product->inStock();
@endphp
<article wire:key="card-{{ $product->id }}" data-reveal="{{ $delay }}" {{ $attributes->class('group relative flex flex-col') }}>
    <div class="relative aspect-[4/5] overflow-hidden rounded-3xl bg-zinc-100 dark:bg-zinc-900">
        <a href="{{ route('product', $product) }}" class="block size-full" tabindex="-1">
            <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" loading="lazy" class="size-full object-cover transition duration-700 ease-out group-hover:scale-105">
        </a>

        <div class="pointer-events-none absolute start-3 top-3 flex flex-col gap-1.5">
            @if ($product->isOnSale())
                <span class="rounded-full bg-amber-500 px-2.5 py-1 text-xs font-bold text-brand-950 shadow">-{{ $product->discountPercent() }}%</span>
            @endif
            @if ($product->isNew())
                <span class="rounded-full bg-white/90 px-2.5 py-1 text-xs font-bold text-brand-800 shadow backdrop-blur">New</span>
            @endif
            @if (! $inStock)
                <span class="rounded-full bg-zinc-900/80 px-2.5 py-1 text-xs font-bold text-white">Sold out</span>
            @endif
        </div>

        <button type="button" wire:click="toggleWishlist({{ $product->id }})" class="absolute end-3 top-3 grid size-10 place-items-center rounded-full bg-white/90 text-zinc-700 shadow backdrop-blur transition hover:scale-110 hover:text-red-500 dark:bg-zinc-900/80 dark:text-zinc-200" aria-label="{{ $wishlisted ? 'Remove from wishlist' : 'Save to wishlist' }}" aria-pressed="{{ $wishlisted ? 'true' : 'false' }}">
            @if ($wishlisted)
                <flux:icon.heart variant="solid" class="size-5 text-red-500" />
            @else
                <flux:icon.heart variant="outline" class="size-5" />
            @endif
        </button>

        @if ($inStock)
            <div class="absolute inset-x-3 bottom-3 translate-y-0 opacity-100 transition duration-300 lg:translate-y-3 lg:opacity-0 lg:group-focus-within:translate-y-0 lg:group-focus-within:opacity-100 lg:group-hover:translate-y-0 lg:group-hover:opacity-100">
                @if ($hasVariants)
                    <a href="{{ route('product', $product) }}" class="flex h-11 w-full items-center justify-center gap-2 rounded-full bg-white/95 text-sm font-bold text-zinc-900 shadow-lg backdrop-blur transition hover:bg-brand-700 hover:text-white">
                        Choose options
                    </a>
                @else
                    <button type="button" wire:click="addToCart({{ $product->id }})" wire:loading.attr="disabled" wire:target="addToCart({{ $product->id }})" class="flex h-11 w-full items-center justify-center gap-2 rounded-full bg-brand-700 text-sm font-bold text-white shadow-lg transition hover:bg-brand-800 disabled:opacity-70">
                        <flux:icon.shopping-bag variant="micro" wire:loading.remove wire:target="addToCart({{ $product->id }})" />
                        <span wire:loading wire:target="addToCart({{ $product->id }})" class="size-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        Add to cart
                    </button>
                @endif
            </div>
        @endif
    </div>

    <div class="mt-4 flex flex-1 flex-col gap-1 px-1">
        <p class="text-xs font-semibold tracking-wide text-zinc-500 uppercase">{{ $product->brand ?? $product->category?->name }}</p>
        <h3 class="font-semibold leading-snug">
            <a href="{{ route('product', $product) }}" class="hover:text-brand-700 dark:hover:text-brand-400">{{ $product->name }}</a>
        </h3>
        @if ($product->reviews_count > 0)
            <x-stars :rating="$product->rating" :count="$product->reviews_count" />
        @endif
        <p class="mt-auto flex items-baseline gap-2 pt-1">
            <span class="text-lg font-extrabold text-brand-900 dark:text-white">{{ naira($product->price) }}</span>
            @if ($product->isOnSale())
                <span class="text-sm text-zinc-400 line-through">{{ naira($product->compare_at_price) }}</span>
            @endif
        </p>
    </div>
</article>
