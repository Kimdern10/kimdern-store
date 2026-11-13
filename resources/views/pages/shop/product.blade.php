<?php

use App\Enums\OrderStatus;
use App\Livewire\Concerns\InteractsWithShop;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\StockAlert;
use App\Support\Seo;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::shop')] class extends Component {
    use InteractsWithShop;

    public Product $product;

    public ?string $size = null;

    public ?string $colour = null;

    public int $quantity = 1;

    public int $rating = 5;

    public string $reviewTitle = '';

    public string $reviewBody = '';

    public bool $reviewSent = false;

    public string $alertEmail = '';

    public bool $alertSaved = false;

    public function mount(Product $product): void
    {
        abort_unless($product->is_active, 404);

        $this->product = $product->load(['images', 'variants', 'category']);

        // Pre-select the only option when there is just one.
        $sizes = $this->product->sizes();
        $colours = $this->product->colours();
        $this->size = $sizes->count() === 1 ? $sizes->first() : null;
        $this->colour = $colours->count() === 1 ? $colours->keys()->first() : null;

        $this->alertEmail = (string) Auth::user()?->email;
        $this->rememberViewed($product);

        Seo::set(
            $product->meta_title ?: $product->name,
            $product->meta_description ?: ($product->short_description ?: Str::limit(strip_tags((string) $product->description), 155)),
            $product->imageUrl(false),
            'product',
        );
    }

    #[Computed]
    public function variant(): ?ProductVariant
    {
        if ($this->product->variants->isEmpty()) {
            return null;
        }

        return $this->product->variants->first(fn (ProductVariant $v) => $v->size === ($this->product->sizes()->isEmpty() ? null : $this->size)
            && $v->colour === ($this->product->colours()->isEmpty() ? null : $this->colour));
    }

    #[Computed]
    public function price(): int
    {
        return $this->variant?->price ?? $this->product->variants->when($this->size, fn ($v) => $v->where('size', $this->size))->min('price') ?? $this->product->price;
    }

    #[Computed]
    public function stockLeft(): ?int
    {
        if ($this->product->variants->isEmpty()) {
            return $this->product->stock;
        }

        return $this->variant?->stock;
    }

    /**
     * Whether a size (or colour) has any stock, given the other choice already made.
     */
    public function optionAvailable(string $type, string $value): bool
    {
        return $this->product->variants->contains(fn (ProductVariant $v) => $v->stock > 0
            && $v->{$type} === $value
            && ($type === 'size' ? ($this->colour === null || $v->colour === $this->colour) : ($this->size === null || $v->size === $this->size)));
    }

    public function updatedSize(): void
    {
        $this->quantity = 1;
    }

    public function updatedColour(): void
    {
        $this->quantity = 1;
    }

    public function addSelectedToCart(): void
    {
        if ($this->product->variants->isNotEmpty() && ! $this->variant) {
            Flux::toast(variant: 'warning', text: 'Please choose '.collect([$this->product->sizes()->isNotEmpty() && ! $this->size ? 'a size' : null, $this->product->colours()->isNotEmpty() && ! $this->colour ? 'a colour' : null])->filter()->implode(' and ').' first.');

            return;
        }

        $this->addToCart($this->product->id, $this->variant?->id, max(1, min($this->quantity, 20)));
    }

    /** @return Collection<int, Review> */
    #[Computed]
    public function reviews(): Collection
    {
        return $this->product->reviews()->where('is_approved', true)->latest()->limit(20)->get();
    }

    /**
     * Keep the last few products this visitor looked at, newest first.
     */
    private function rememberViewed(Product $product): void
    {
        $ids = collect(session('recently_viewed', []))->reject(fn ($id) => (int) $id === $product->id)->prepend($product->id)->take(9)->values()->all();
        session()->put('recently_viewed', $ids);
    }

    /** @return Collection<int, Product> */
    #[Computed]
    public function recentlyViewed(): Collection
    {
        $ids = collect(session('recently_viewed', []))->map(fn ($id) => (int) $id)->reject(fn (int $id) => $id === $this->product->id)->take(4)->values();

        if ($ids->isEmpty()) {
            return new Collection;
        }

        return Product::query()->forCards()->whereIn('id', $ids)->get()->sortBy(fn (Product $p) => $ids->search($p->id))->values();
    }

    /**
     * "Email me when it is back": saved once per email and option.
     */
    public function requestStockAlert(): void
    {
        $this->validate(['alertEmail' => ['required', 'email:rfc', 'max:255']], [], ['alertEmail' => 'email']);

        StockAlert::query()->firstOrCreate([
            'product_id' => $this->product->id,
            'product_variant_id' => $this->variant?->id,
            'email' => strtolower(trim($this->alertEmail)),
            'notified_at' => null,
        ]);

        $this->alertSaved = true;
    }

    /** @return Collection<int, Product> */
    #[Computed]
    public function related(): Collection
    {
        return Product::query()->forCards()->where('category_id', $this->product->category_id)->whereKeyNot($this->product->id)->orderByDesc('sales_count')->limit(4)->get();
    }

    #[Computed]
    public function hasBought(): bool
    {
        return Auth::check() && Auth::user()->orders()
            ->whereIn('status', [OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered])
            ->whereHas('items', fn ($q) => $q->where('product_id', $this->product->id))->exists();
    }

    public function submitReview(): void
    {
        abort_unless(Auth::check(), 403);

        $this->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'reviewTitle' => ['nullable', 'string', 'max:120'],
            'reviewBody' => ['required', 'string', 'min:10', 'max:2000'],
        ], [], ['reviewBody' => 'review', 'reviewTitle' => 'title']);

        $this->product->reviews()->create([
            'user_id' => Auth::id(),
            'name' => Auth::user()->name,
            'rating' => $this->rating,
            'title' => $this->reviewTitle ?: null,
            'body' => $this->reviewBody,
            'is_verified_buyer' => $this->hasBought,
            'is_approved' => false,
        ]);

        $this->reset('reviewTitle', 'reviewBody');
        $this->rating = 5;
        $this->reviewSent = true;
    }
}; ?>

<div>
    <x-slot:head>
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->name,
            'image' => $product->images->map(fn ($i) => $i->url())->all(),
            'description' => $product->short_description,
            'sku' => $product->sku,
            'brand' => ['@type' => 'Brand', 'name' => $product->brand],
            'offers' => ['@type' => 'Offer', 'priceCurrency' => 'NGN', 'price' => $product->price / 100, 'availability' => $product->inStock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock', 'url' => route('product', $product)],
        ] + ($product->reviews_count > 0 ? ['aggregateRating' => ['@type' => 'AggregateRating', 'ratingValue' => $product->rating, 'reviewCount' => $product->reviews_count]] : []), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    </x-slot:head>

    <div class="container-shop pt-6">
        <nav class="text-sm text-zinc-500" aria-label="Breadcrumb">
            <a href="{{ route('home') }}" wire:navigate.hover class="hover:text-brand-700">Home</a> <span class="mx-1">/</span>
            <a href="{{ route('category', $product->category) }}" wire:navigate.hover class="hover:text-brand-700">{{ $product->category->name }}</a> <span class="mx-1">/</span>
            <span class="text-zinc-800 dark:text-zinc-200">{{ $product->name }}</span>
        </nav>

        <div class="mt-6 grid gap-10 lg:grid-cols-2 lg:gap-14">
            {{-- Gallery --}}
            @php($images = $product->images->isNotEmpty() ? $product->images->map(fn ($i) => $i->url())->all() : [\App\Support\Media::placeholder()])
            <div x-data="{ active: 0, zoom: false, images: @js($images) }" class="lg:sticky lg:top-28 lg:self-start">
                <div class="group relative aspect-square overflow-hidden rounded-[2rem] bg-zinc-100 dark:bg-zinc-900">
                    <template x-for="(src, i) in images" :key="i">
                        <img :src="src" alt="{{ $product->name }}" x-show="active === i" x-transition:enter="transition duration-500" x-transition:enter-start="opacity-0 scale-105" class="absolute inset-0 size-full cursor-zoom-in object-cover" x-on:click="zoom = true">
                    </template>
                    @if ($product->isOnSale())
                        <span class="absolute start-4 top-4 rounded-full bg-amber-500 px-3 py-1.5 text-sm font-bold text-brand-950 shadow">-{{ $product->discountPercent() }}%</span>
                    @endif
                    <template x-if="images.length > 1">
                        <div>
                            <button type="button" x-on:click="active = (active - 1 + images.length) % images.length" class="absolute start-4 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/90 shadow transition hover:scale-110 dark:bg-zinc-900/90" aria-label="Previous photo"><flux:icon.chevron-left /></button>
                            <button type="button" x-on:click="active = (active + 1) % images.length" class="absolute end-4 top-1/2 grid size-11 -translate-y-1/2 place-items-center rounded-full bg-white/90 shadow transition hover:scale-110 dark:bg-zinc-900/90" aria-label="Next photo"><flux:icon.chevron-right /></button>
                        </div>
                    </template>
                    <button type="button" x-on:click="zoom = true" class="absolute end-4 bottom-4 grid size-11 place-items-center rounded-full bg-white/90 shadow dark:bg-zinc-900/90" aria-label="View larger"><flux:icon.arrows-pointing-out variant="micro" /></button>
                </div>
                <div class="no-scrollbar mt-4 flex gap-3 overflow-x-auto" x-show="images.length > 1">
                    <template x-for="(src, i) in images" :key="'t' + i">
                        <button type="button" x-on:click="active = i" class="size-20 shrink-0 overflow-hidden rounded-2xl ring-2 ring-offset-2 transition dark:ring-offset-zinc-950" :class="active === i ? 'ring-brand-600' : 'ring-transparent opacity-70 hover:opacity-100'" :aria-label="'Photo ' + (i + 1)">
                            <img :src="src" alt="" class="size-full object-cover">
                        </button>
                    </template>
                </div>

                {{-- Lightbox --}}
                <template x-teleport="body">
                    <div x-cloak x-show="zoom" x-transition.opacity x-on:keydown.escape.window="zoom = false" x-on:keydown.arrow-right.window="zoom && (active = (active + 1) % images.length)" x-on:keydown.arrow-left.window="zoom && (active = (active - 1 + images.length) % images.length)" class="fixed inset-0 z-[60] grid place-items-center bg-zinc-950/95 p-4" x-on:click.self="zoom = false" role="dialog" aria-modal="true" aria-label="Product photos">
                        <img :src="images[active]" alt="{{ $product->name }}" class="max-h-[88vh] max-w-full rounded-2xl object-contain">
                        <button type="button" x-on:click="zoom = false" class="absolute end-4 top-4 grid size-12 place-items-center rounded-full bg-white/10 text-white hover:bg-white/20" aria-label="Close"><flux:icon.x-mark /></button>
                    </div>
                </template>
            </div>

            {{-- Details --}}
            <div>
                <p class="text-sm font-bold tracking-widest text-amber-600 uppercase dark:text-amber-400">{{ $product->brand }}</p>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-brand-950 sm:text-4xl dark:text-white">{{ $product->name }}</h1>

                <div class="mt-3 flex flex-wrap items-center gap-3">
                    @if ($product->reviews_count > 0)
                        <a href="#reviews" class="flex items-center gap-2"><x-stars :rating="$product->rating" /> <span class="text-sm text-zinc-500">{{ number_format($product->rating, 1) }} · {{ $product->reviews_count }} {{ \Illuminate\Support\Str::plural('review', $product->reviews_count) }}</span></a>
                    @endif
                    @if ($product->sku)
                        <span class="text-xs text-zinc-400">SKU {{ $product->sku }}</span>
                    @endif
                </div>

                <div class="mt-6 flex items-baseline gap-3">
                    <span class="text-3xl font-extrabold text-brand-900 dark:text-white">{{ naira($this->price) }}</span>
                    @if ($product->isOnSale() && ! $this->variant?->price)
                        <span class="text-lg text-zinc-400 line-through">{{ naira($product->compare_at_price) }}</span>
                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Save {{ naira($product->compare_at_price - $product->price) }}</span>
                    @endif
                </div>

                @if ($product->short_description)
                    <p class="mt-5 text-lg leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $product->short_description }}</p>
                @endif

                <form wire:submit="addSelectedToCart" class="mt-8 space-y-6 rounded-3xl border bg-zinc-50/60 p-5 sm:p-6 dark:bg-zinc-900/50">
                    @if ($product->sizes()->isNotEmpty())
                        <fieldset>
                            <legend class="flex w-full justify-between text-sm font-bold">Size <span class="font-normal text-zinc-500">{{ $size ?? 'Choose a size' }}</span></legend>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($product->sizes() as $option)
                                    @php($available = $this->optionAvailable('size', $option))
                                    <label class="cursor-pointer">
                                        <input type="radio" wire:model.live="size" value="{{ $option }}" class="peer sr-only">
                                        <span @class(['grid h-11 min-w-12 place-items-center rounded-xl border-2 bg-white px-3 text-sm font-bold transition peer-checked:border-brand-700 peer-checked:bg-brand-700 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 hover:border-brand-600 dark:bg-zinc-900', 'text-zinc-400 line-through opacity-60' => ! $available])>{{ $option }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif

                    @if ($product->colours()->isNotEmpty())
                        <fieldset>
                            <legend class="flex w-full justify-between text-sm font-bold">Colour <span class="font-normal text-zinc-500">{{ $colour ?? 'Choose a colour' }}</span></legend>
                            <div class="mt-3 flex flex-wrap gap-3">
                                @foreach ($product->colours() as $name => $hex)
                                    @php($available = $this->optionAvailable('colour', $name))
                                    <label class="cursor-pointer" title="{{ $name }}">
                                        <input type="radio" wire:model.live="colour" value="{{ $name }}" class="peer sr-only">
                                        <span @class(['block size-10 rounded-full ring-2 ring-transparent ring-offset-2 transition peer-checked:ring-brand-700 peer-focus-visible:ring-brand-500 hover:scale-110 dark:ring-offset-zinc-900', 'opacity-40' => ! $available]) style="background: {{ $hex ?: '#ccc' }}; box-shadow: inset 0 0 0 1px rgb(0 0 0 / .12)">
                                            <span class="sr-only">{{ $name }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif

                    <div class="flex items-center gap-2 text-sm font-semibold">
                        @if ($this->stockLeft === null)
                            <span class="text-zinc-500">Choose your options to see stock.</span>
                        @elseif ($this->stockLeft < 1)
                            <span class="size-2 rounded-full bg-red-500"></span><span class="text-red-600">Out of stock</span>
                        @elseif ($this->stockLeft <= (int) \App\Models\Setting::get('low_stock_threshold', '5'))
                            <span class="size-2 animate-pulse rounded-full bg-amber-500"></span><span class="text-amber-700 dark:text-amber-400">Hurry, only {{ $this->stockLeft }} left</span>
                        @else
                            <span class="size-2 rounded-full bg-brand-500"></span><span class="text-brand-700 dark:text-brand-400">In stock, ready to ship</span>
                        @endif
                    </div>

                    @if ($this->stockLeft === 0 || ($this->stockLeft === null && ! $product->inStock()))
                        <div class="rounded-2xl bg-white p-4 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800" wire:key="stock-alert-{{ $this->variant?->id ?? 'all' }}">
                            @if ($alertSaved)
                                <p class="flex items-center gap-2 text-sm font-semibold text-brand-700 dark:text-brand-400"><flux:icon.bell-alert variant="micro" /> Done! We'll email {{ $alertEmail }} when it's back.</p>
                            @else
                                <p class="text-sm font-semibold">Sold out{{ $this->variant ? ' in '.$this->variant->label() : '' }}. Get an email when it's back:</p>
                                <div class="mt-3 flex gap-2">
                                    <flux:input wire:model="alertEmail" type="email" placeholder="Your email" aria-label="Email for stock alert" class="flex-1" />
                                    <flux:button type="button" wire:click="requestStockAlert" icon="bell">Notify me</flux:button>
                                </div>
                                @error('alertEmail')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            @endif
                        </div>
                    @endif

                    <div class="flex gap-3">
                        <div class="flex h-13 items-center rounded-full border-2 bg-white dark:bg-zinc-900">
                            <button type="button" wire:click="$set('quantity', {{ max(1, $quantity - 1) }})" class="grid size-12 place-items-center rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="One less"><flux:icon.minus variant="micro" /></button>
                            <span class="w-8 text-center font-bold" aria-live="polite">{{ $quantity }}</span>
                            <button type="button" wire:click="$set('quantity', {{ min($quantity + 1, max(1, $this->stockLeft ?? 20), 20) }})" class="grid size-12 place-items-center rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="One more"><flux:icon.plus variant="micro" /></button>
                        </div>
                        <button type="submit" @disabled($this->stockLeft !== null && $this->stockLeft < 1) class="flex h-13 flex-1 items-center justify-center gap-2 rounded-full bg-brand-700 px-6 font-bold text-white shadow-lg shadow-brand-700/25 transition hover:-translate-y-0.5 hover:bg-brand-800 disabled:translate-y-0 disabled:cursor-not-allowed disabled:bg-zinc-300 disabled:shadow-none dark:disabled:bg-zinc-700">
                            <span wire:loading.remove wire:target="addSelectedToCart" class="flex items-center gap-2"><flux:icon.shopping-bag variant="micro" /> Add to cart</span>
                            <span wire:loading wire:target="addSelectedToCart" class="size-5 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                        </button>
                        <button type="button" wire:click="toggleWishlist({{ $product->id }})" class="grid size-13 shrink-0 place-items-center rounded-full border-2 bg-white transition hover:border-red-300 hover:text-red-500 dark:bg-zinc-900" aria-label="Save to wishlist">
                            @if (in_array($product->id, $this->wishlistIds))
                                <flux:icon.heart variant="solid" class="text-red-500" />
                            @else
                                <flux:icon.heart variant="outline" />
                            @endif
                        </button>
                    </div>
                </form>

                <ul class="mt-6 grid gap-3 text-sm sm:grid-cols-3">
                    @foreach ([['truck', 'Nationwide delivery', 'Lagos in 1–2 days'], ['shield-check', '100% genuine', 'Sourced from brands'], ['arrow-path', 'Easy returns', 'Within 7 days']] as [$icon, $title, $sub])
                        <li class="flex items-center gap-3 rounded-2xl border p-3">
                            <flux:icon :name="$icon" class="size-6 shrink-0 text-brand-600" />
                            <div><p class="font-semibold">{{ $title }}</p><p class="text-xs text-zinc-500">{{ $sub }}</p></div>
                        </li>
                    @endforeach
                </ul>

                <div x-data="{ tab: 'details' }" class="mt-10">
                    <div class="flex gap-6 border-b text-sm font-bold" role="tablist">
                        <button type="button" role="tab" x-on:click="tab = 'details'" :class="tab === 'details' ? 'border-brand-700 text-brand-800 dark:text-brand-400 dark:border-brand-400' : 'border-transparent text-zinc-500'" class="-mb-px border-b-2 pb-3">Details</button>
                        <button type="button" role="tab" x-on:click="tab = 'delivery'" :class="tab === 'delivery' ? 'border-brand-700 text-brand-800 dark:text-brand-400 dark:border-brand-400' : 'border-transparent text-zinc-500'" class="-mb-px border-b-2 pb-3">Delivery & returns</button>
                    </div>
                    <div x-show="tab === 'details'" class="prose-shop pt-5 text-zinc-600 dark:text-zinc-400">
                        {!! \Illuminate\Support\Str::markdown((string) $product->description, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                    </div>
                    <div x-cloak x-show="tab === 'delivery'" class="prose-shop pt-5 text-zinc-600 dark:text-zinc-400">
                        <ul>
                            @foreach (\App\Models\DeliveryZone::query()->where('is_active', true)->orderBy('sort_order')->get() as $zone)
                                <li><strong>{{ $zone->name }}:</strong> {{ naira($zone->fee) }} · {{ $zone->eta }}@if ($zone->free_over) · free over {{ naira($zone->free_over) }}@endif</li>
                            @endforeach
                        </ul>
                        <p>Not happy? Return unused items in their original packaging within 7 days of delivery. <a href="{{ route('shipping') }}" wire:navigate.hover class="font-semibold text-brand-700 underline dark:text-brand-400">Read the full policy</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Reviews --}}
    <section id="reviews" class="container-shop mt-24 scroll-mt-28" aria-labelledby="reviews-title">
        <div class="grid gap-10 lg:grid-cols-[20rem_1fr]">
            <div data-reveal>
                <h2 id="reviews-title" class="text-2xl font-extrabold text-brand-950 dark:text-white">Customer reviews</h2>
                @if ($product->reviews_count > 0)
                    <div class="mt-4 flex items-center gap-3">
                        <span class="text-5xl font-extrabold">{{ number_format($product->rating, 1) }}</span>
                        <div><x-stars :rating="$product->rating" size="size-5" /><p class="text-sm text-zinc-500">{{ $product->reviews_count }} {{ \Illuminate\Support\Str::plural('review', $product->reviews_count) }}</p></div>
                    </div>
                    <div class="mt-5 space-y-1.5">
                        @foreach (range(5, 1) as $star)
                            @php($share = $this->reviews->count() ? $this->reviews->where('rating', $star)->count() / $this->reviews->count() * 100 : 0)
                            <div class="flex items-center gap-2 text-xs">
                                <span class="w-3">{{ $star }}</span>
                                <div class="h-2 flex-1 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800"><div class="h-full rounded-full bg-amber-400" style="width: {{ $share }}%"></div></div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-3 text-zinc-500">No reviews yet. Be the first to share your thoughts!</p>
                @endif

                <div class="mt-8 rounded-3xl border p-5">
                    @auth
                        @if ($reviewSent)
                            <p class="font-semibold text-brand-700 dark:text-brand-400">Thank you! Your review will appear once we have checked it.</p>
                        @else
                            <form wire:submit="submitReview" class="space-y-4">
                                <p class="font-bold">Write a review</p>
                                <div class="flex gap-1" role="radiogroup" aria-label="Your rating">
                                    @foreach (range(1, 5) as $star)
                                        <button type="button" wire:click="$set('rating', {{ $star }})" role="radio" aria-checked="{{ $rating === $star ? 'true' : 'false' }}" aria-label="{{ $star }} star{{ $star > 1 ? 's' : '' }}">
                                            <svg viewBox="0 0 20 20" class="size-7 transition hover:scale-110 {{ $star <= $rating ? 'fill-amber-400' : 'fill-zinc-200 dark:fill-zinc-700' }}"><path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.8l-5.2 2.8 1-5.8L1.5 7.7l5.9-.9L10 1.5z" /></svg>
                                        </button>
                                    @endforeach
                                </div>
                                <flux:input wire:model="reviewTitle" label="Title (optional)" placeholder="Sum it up" />
                                <flux:textarea wire:model="reviewBody" label="Your review" rows="4" placeholder="What did you like or not like?" />
                                <flux:button type="submit" variant="primary" class="w-full">Send review</flux:button>
                            </form>
                        @endif
                    @else
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Bought this? <a href="{{ route('login') }}" wire:navigate.hover class="font-semibold text-brand-700 underline dark:text-brand-400">Log in</a> to write a review.</p>
                    @endauth
                </div>
            </div>

            <div class="space-y-5">
                @forelse ($this->reviews as $review)
                    <article wire:key="review-{{ $review->id }}" data-reveal class="rounded-3xl border p-6">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <x-stars :rating="$review->rating" />
                            <time class="text-xs text-zinc-500" datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->format('j M Y') }}</time>
                        </div>
                        @if ($review->title)<h3 class="mt-3 font-bold">{{ $review->title }}</h3>@endif
                        <p class="mt-2 text-zinc-600 dark:text-zinc-400">{{ $review->body }}</p>
                        <p class="mt-4 text-sm font-semibold">{{ $review->name }}@if ($review->location)<span class="font-normal text-zinc-500"> · {{ $review->location }}</span>@endif
                            @if ($review->is_verified_buyer)
                                <span class="ms-2 inline-flex items-center gap-1 rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-700 dark:bg-brand-950 dark:text-brand-300"><flux:icon.check-badge variant="micro" /> Verified buyer</span>
                            @endif
                        </p>
                    </article>
                @empty
                    <div class="rounded-3xl border border-dashed p-10 text-center text-zinc-500">Reviews from shoppers will show here.</div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Related --}}
    @if ($this->related->isNotEmpty())
        <section class="container-shop mt-24" aria-labelledby="related-title">
            <h2 id="related-title" class="text-2xl font-extrabold text-brand-950 sm:text-3xl dark:text-white" data-reveal>You may also like</h2>
            <div class="mt-8 grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 lg:grid-cols-4">
                @foreach ($this->related as $i => $item)
                    <x-product-card :product="$item" :wishlisted="in_array($item->id, $this->wishlistIds)" :delay="$i * 80" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Recently viewed --}}
    @if ($this->recentlyViewed->isNotEmpty())
        <section class="container-shop mt-24" aria-labelledby="recent-title">
            <h2 id="recent-title" class="text-2xl font-extrabold text-brand-950 sm:text-3xl dark:text-white" data-reveal>Recently viewed</h2>
            <div class="mt-8 grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 lg:grid-cols-4">
                @foreach ($this->recentlyViewed as $i => $item)
                    <x-product-card :product="$item" :wishlisted="in_array($item->id, $this->wishlistIds)" :delay="$i * 80" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Sticky add-to-cart bar on phones --}}
    <div class="fixed inset-x-0 bottom-0 z-30 border-t bg-white/95 p-3 backdrop-blur lg:hidden dark:bg-zinc-950/95">
        <div class="flex items-center gap-3">
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold">{{ $product->name }}</p>
                <p class="font-extrabold text-brand-800 dark:text-brand-300">{{ naira($this->price) }}</p>
            </div>
            <button type="button" wire:click="addSelectedToCart" @disabled($this->stockLeft !== null && $this->stockLeft < 1) class="h-12 rounded-full bg-brand-700 px-6 font-bold text-white disabled:bg-zinc-300">Add to cart</button>
        </div>
    </div>
</div>
