<?php

use App\Livewire\Concerns\InteractsWithShop;
use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::shop')] class extends Component {
    use InteractsWithShop;

    public function mount(): void
    {
        Seo::set(image: Banner::query()->where('is_active', true)->orderBy('sort_order')->first()?->imageUrl());
    }

    /** @return Collection<int, Banner> */
    #[Computed]
    public function banners(): Collection
    {
        return Banner::query()->where('is_active', true)->orderBy('sort_order')->get();
    }

    /** @return Collection<int, Category> */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->active()->withCount(['products' => fn ($q) => $q->active()])->get();
    }

    /** @return Collection<int, Product> */
    #[Computed]
    public function featured(): Collection
    {
        return Product::query()->forCards()->where('is_featured', true)->latest()->limit(8)->get();
    }

    /** @return Collection<int, Product> */
    #[Computed]
    public function newArrivals(): Collection
    {
        return Product::query()->forCards()->latest()->limit(10)->get();
    }

    /** @return Collection<int, Product> */
    #[Computed]
    public function flashSale(): Collection
    {
        if (! \App\Models\Setting::flashSaleEndsAt()) {
            return new Collection;
        }

        return Product::query()->forCards()
            ->whereNotNull('compare_at_price')->whereColumn('compare_at_price', '>', 'price')
            ->orderByRaw('(compare_at_price - price) * 1.0 / compare_at_price desc')
            ->limit(8)->get();
    }

    /** @return Collection<int, Product> */
    #[Computed]
    public function hairPicks(): Collection
    {
        return Product::query()->forCards()
            ->whereHas('category', fn ($q) => $q->where('department', 'hair'))
            ->orderByDesc('is_featured')->orderByDesc('sales_count')
            ->limit(4)->get();
    }

    /** @return Collection<int, Product> */
    #[Computed]
    public function bestSellers(): Collection
    {
        return Product::query()->forCards()->orderByDesc('sales_count')->limit(4)->get();
    }

    /** @return Collection<int, Review> */
    #[Computed]
    public function testimonials(): Collection
    {
        return Review::query()->with('product:id,name,slug')->where('is_approved', true)->where('rating', '>=', 4)->latest()->limit(6)->get();
    }
}; ?>

<div>
    {{-- Hero slider --}}
    @if ($this->banners->isNotEmpty())
        <section
            x-data="{ active: 0, count: {{ $this->banners->count() }}, timer: null,
                start() { this.timer = setInterval(() => this.next(), 6500) },
                next() { this.active = (this.active + 1) % this.count },
                go(i) { this.active = i; clearInterval(this.timer); this.start() } }"
            x-init="start()"
            class="container-shop pt-4 lg:pt-6"
            aria-roledescription="carousel"
            aria-label="Highlights"
        >
            <div class="relative h-[34rem] overflow-hidden rounded-[2rem] bg-brand-950 sm:h-[38rem]">
                @foreach ($this->banners as $i => $banner)
                    <div
                        x-show="active === {{ $i }}"
                        x-transition:enter="transition delay-300 duration-700 ease-out"
                        x-transition:enter-start="opacity-0"
                        x-transition:leave="transition duration-300 ease-in absolute inset-0"
                        x-transition:leave-end="opacity-0"
                        @if ($i > 0) x-cloak @endif
                        class="absolute inset-0"
                        aria-roledescription="slide"
                    >
                        <img decoding="async" src="{{ $banner->imageUrl() }}" alt="" class="absolute inset-0 size-full object-cover" :class="active === {{ $i }} && 'scale-110'" style="transition: transform 7s ease-out" @if ($i > 0) loading="lazy" @else fetchpriority="high" @endif>
                        <div class="absolute inset-0 bg-gradient-to-r from-brand-950/90 via-brand-950/55 to-transparent"></div>
                        <div class="relative flex h-full max-w-2xl flex-col justify-end gap-5 p-7 pb-20 sm:justify-center sm:p-14">
                            @if ($banner->eyebrow)
                                <span class="w-fit rounded-full border border-amber-400/40 bg-amber-400/15 px-4 py-1.5 text-xs font-bold tracking-widest text-amber-300 uppercase backdrop-blur">{{ $banner->eyebrow }}</span>
                            @endif
                            <h1 class="text-4xl leading-[1.05] font-extrabold tracking-tight text-white sm:text-6xl">{{ $banner->title }}</h1>
                            @if ($banner->subtitle)
                                <p class="max-w-lg text-base text-white/80 sm:text-lg">{{ $banner->subtitle }}</p>
                            @endif
                            <div class="flex flex-wrap gap-3 pt-2">
                                @if ($banner->link)
                                    <a href="{{ $banner->link }}" class="inline-flex h-13 items-center gap-2 rounded-full bg-amber-500 px-7 font-bold text-brand-950 shadow-xl shadow-amber-500/25 transition hover:-translate-y-0.5 hover:bg-amber-400">
                                        {{ $banner->button_text ?: 'Shop now' }} <flux:icon.arrow-right variant="micro" />
                                    </a>
                                @endif
                                <a href="{{ route('shop') }}" wire:navigate.hover class="inline-flex h-13 items-center rounded-full border border-white/30 px-7 font-semibold text-white backdrop-blur transition hover:bg-white/10">Browse all</a>
                            </div>
                        </div>
                    </div>
                @endforeach

                @if ($this->banners->count() > 1)
                    <div class="absolute start-7 bottom-7 flex gap-2 sm:start-14">
                        @foreach ($this->banners as $i => $banner)
                            <button type="button" x-on:click="go({{ $i }})" class="h-1.5 rounded-full bg-white/40 transition-all duration-500" :class="active === {{ $i }} ? 'w-10 !bg-amber-400' : 'w-4 hover:bg-white/70'" aria-label="Show slide {{ $i + 1 }}"></button>
                        @endforeach
                    </div>
                @endif

                <div class="absolute end-8 bottom-8 hidden animate-float rounded-3xl border border-white/20 bg-white/10 p-5 text-white backdrop-blur-md lg:block">
                    <p class="text-xs tracking-widest text-white/70 uppercase">Rated by shoppers</p>
                    <div class="mt-1 flex items-center gap-2"><span class="text-3xl font-extrabold">4.8</span><x-stars :rating="4.8" /></div>
                    <p class="text-sm text-white/80">from 2,000+ happy customers</p>
                </div>
            </div>
        </section>
    @endif

    {{-- Trust strip --}}
    <section class="overflow-hidden border-y bg-zinc-50 py-4 mt-10 dark:bg-zinc-900/50" aria-label="Why shop with us">
        <div class="flex w-max animate-marquee gap-12 whitespace-nowrap">
            @foreach (range(1, 2) as $loopCopy)
                @foreach ([['check-badge', '100% genuine products'], ['truck', 'Delivery to all 36 states'], ['lock-closed', 'Secure card & bank payments'], ['arrow-path', 'Easy 7-day returns'], ['chat-bubble-left-right', 'Real people on WhatsApp'], ['gift', 'Gift wrapping available']] as [$icon, $text])
                    <span class="flex items-center gap-2 text-sm font-semibold text-zinc-600 dark:text-zinc-300" @if ($loopCopy === 2) aria-hidden="true" @endif>
                        <flux:icon :name="$icon" variant="mini" class="text-brand-600 dark:text-brand-400" /> {{ $text }}
                    </span>
                @endforeach
            @endforeach
        </div>
    </section>

    {{-- Flash sale --}}
    @if ($this->flashSale->isNotEmpty() && ($endsAt = \App\Models\Setting::flashSaleEndsAt()))
        <section class="mt-16" aria-labelledby="flash-heading"
            x-data="{
                end: {{ $endsAt->getTimestampMs() }}, left: { d: '00', h: '00', m: '00', s: '00' }, over: false,
                tick() {
                    const ms = Math.max(0, this.end - Date.now());
                    if (ms === 0) { this.over = true }
                    const pad = (n) => String(n).padStart(2, '0');
                    this.left = { d: pad(Math.floor(ms / 864e5)), h: pad(Math.floor(ms / 36e5) % 24), m: pad(Math.floor(ms / 6e4) % 60), s: pad(Math.floor(ms / 1e3) % 60) };
                },
            }"
            x-init="tick(); setInterval(() => tick(), 1000)"
            x-show="! over"
        >
            <div class="container-shop">
                <div class="flex flex-col gap-5 rounded-[2rem] bg-gradient-to-r from-amber-500 via-amber-400 to-orange-400 p-6 text-brand-950 sm:flex-row sm:items-center sm:justify-between sm:p-8">
                    <div class="flex items-center gap-4">
                        <span class="grid size-14 shrink-0 animate-pulse place-items-center rounded-2xl bg-brand-950 text-amber-400"><flux:icon.bolt class="size-7" /></span>
                        <div>
                            <p class="text-xs font-extrabold tracking-widest uppercase opacity-80">Limited time</p>
                            <h2 id="flash-heading" class="text-2xl font-extrabold sm:text-3xl">{{ \App\Models\Setting::get('flash_sale_title') ?: 'Flash sale' }}</h2>
                        </div>
                    </div>
                    <div class="flex items-center gap-2" role="timer" aria-label="Time left in the sale">
                        <template x-for="[label, key] in [['Days', 'd'], ['Hrs', 'h'], ['Min', 'm'], ['Sec', 's']]" :key="key">
                            <div class="flex w-16 flex-col items-center rounded-2xl bg-brand-950 py-2 text-white">
                                <span class="text-2xl font-extrabold tabular-nums" x-text="left[key]"></span>
                                <span class="text-[10px] tracking-widest text-amber-300 uppercase" x-text="label"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            <div class="no-scrollbar mt-8 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 sm:gap-6 sm:px-6 lg:px-[max(2rem,calc((100vw-80rem)/2+2rem))]">
                @foreach ($this->flashSale as $product)
                    <x-product-card :product="$product" :wishlisted="in_array($product->id, $this->wishlistIds)" class="w-[46%] shrink-0 snap-start sm:w-[30%] lg:w-[23%]" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Categories --}}
    <section class="container-shop mt-20" aria-labelledby="categories-heading">
        <div class="flex items-end justify-between gap-4" data-reveal>
            <div>
                <p class="text-sm font-bold tracking-widest text-amber-600 uppercase dark:text-amber-400">Shop by category</p>
                <h2 id="categories-heading" class="mt-1 text-3xl font-extrabold tracking-tight text-brand-950 sm:text-4xl dark:text-white">What are you shopping for?</h2>
            </div>
            <a href="{{ route('shop') }}" wire:navigate.hover class="hidden shrink-0 font-semibold text-brand-700 hover:underline sm:block dark:text-brand-400">View all →</a>
        </div>
        <div class="mt-8 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
            @foreach ($this->categories as $i => $category)
                <a href="{{ route('category', $category) }}" wire:navigate.hover data-reveal="{{ ($i % 4) * 80 }}" class="group relative aspect-[4/3] overflow-hidden rounded-3xl bg-zinc-200 sm:aspect-square dark:bg-zinc-800">
                    @if ($category->imageUrl())
                        <img decoding="async" src="{{ $category->imageUrl() }}" alt="" loading="lazy" class="absolute inset-0 size-full object-cover transition duration-700 group-hover:scale-110">
                    @endif
                    <div class="absolute inset-0 bg-gradient-to-t from-brand-950/85 via-brand-950/10 to-transparent transition group-hover:from-brand-950/95"></div>
                    <div class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-2 p-4 sm:p-5">
                        <div>
                            <h3 class="text-lg font-bold text-white sm:text-xl">{{ $category->name }}</h3>
                            <p class="text-xs text-white/70 sm:text-sm">{{ $category->products_count }} products</p>
                        </div>
                        <span class="hidden size-10 shrink-0 place-items-center rounded-full bg-white text-brand-900 transition duration-300 group-hover:-rotate-45 group-hover:bg-amber-400 sm:grid">
                            <flux:icon.arrow-right variant="micro" />
                        </span>
                    </div>
                </a>
            @endforeach
            <a href="{{ route('shop') }}" wire:navigate.hover data-reveal="240" class="group flex aspect-[4/3] flex-col items-center justify-center gap-3 rounded-3xl border-2 border-dashed border-brand-200 bg-brand-50/60 p-4 text-center transition hover:border-brand-500 hover:bg-brand-50 sm:aspect-square dark:border-brand-900 dark:bg-brand-950/30">
                <span class="grid size-14 place-items-center rounded-full bg-brand-700 text-white transition group-hover:scale-110"><flux:icon.squares-2x2 /></span>
                <span class="text-lg font-bold text-brand-900 dark:text-white">Shop everything</span>
                <span class="text-sm text-zinc-500">{{ \App\Models\Product::query()->active()->count() }} products</span>
            </a>
        </div>
    </section>

    {{-- Featured --}}
    @if ($this->featured->isNotEmpty())
        <section class="container-shop mt-24" aria-labelledby="featured-heading">
            <div class="flex items-end justify-between gap-4" data-reveal>
                <div>
                    <p class="text-sm font-bold tracking-widest text-amber-600 uppercase dark:text-amber-400">Hand-picked for you</p>
                    <h2 id="featured-heading" class="mt-1 text-3xl font-extrabold tracking-tight text-brand-950 sm:text-4xl dark:text-white">Featured products</h2>
                </div>
                <a href="{{ route('shop') }}" wire:navigate.hover class="hidden shrink-0 font-semibold text-brand-700 hover:underline sm:block dark:text-brand-400">Shop all →</a>
            </div>
            <div class="mt-8 grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 lg:grid-cols-4">
                @foreach ($this->featured as $i => $product)
                    <x-product-card :product="$product" :wishlisted="in_array($product->id, $this->wishlistIds)" :delay="($i % 4) * 80" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Hair studio --}}
    @if ($this->hairPicks->isNotEmpty())
        <section class="container-shop mt-24" aria-labelledby="hair-heading">
            <div class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-zinc-950 via-brand-950 to-zinc-900 p-6 text-white sm:p-10 lg:p-12">
                <div class="grid gap-10 lg:grid-cols-[22rem_1fr] lg:items-center">
                    <div data-reveal>
                        <p class="text-sm font-bold tracking-widest text-amber-400 uppercase">The Hair Studio</p>
                        <h2 id="hair-heading" class="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Wigs, braids & everything hair</h2>
                        <p class="mt-3 text-white/75">Bone straight, body wave, curly and bobs in lengths from 10 to 22 inches. Human hair options, packed discreetly and delivered nationwide.</p>
                        <div class="mt-6 flex flex-wrap gap-2">
                            @foreach ($this->categories->where('department', 'hair') as $hairCategory)
                                <a href="{{ route('category', $hairCategory) }}" wire:navigate.hover class="rounded-full border border-white/20 bg-white/5 px-4 py-2 text-sm font-semibold backdrop-blur transition hover:border-amber-400 hover:bg-amber-400 hover:text-brand-950">{{ $hairCategory->name }}</a>
                            @endforeach
                        </div>
                        <a href="{{ route('shop', ['department' => 'hair']) }}" wire:navigate.hover class="mt-8 inline-flex h-12 items-center gap-2 rounded-full bg-amber-500 px-6 font-bold text-brand-950 transition hover:-translate-y-0.5 hover:bg-amber-400">Shop all hair <flux:icon.arrow-right variant="micro" /></a>
                    </div>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-8 sm:gap-x-5 xl:grid-cols-4 [&_h3]:text-white [&_.text-brand-900]:!text-amber-300">
                        @foreach ($this->hairPicks as $i => $product)
                            <x-product-card :product="$product" :wishlisted="in_array($product->id, $this->wishlistIds)" :delay="$i * 80" />
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Beauty / Fashion split --}}
    <section class="container-shop mt-24 grid gap-5 lg:grid-cols-2" aria-label="Departments">
        @foreach ([['beauty', 'Beauty edit', 'Skincare, makeup & scents that work for melanin-rich skin.', $this->categories->firstWhere('slug', 'makeup')?->imageUrl(false) ?? \App\Support\Media::placeholder()], ['fashion', 'Fashion edit', 'Fresh fits for work, weekends and every owambe.', $this->categories->firstWhere('slug', 'women')?->imageUrl(false) ?? \App\Support\Media::placeholder()]] as $i => [$department, $heading, $text, $image])
            <a href="{{ route('shop', ['department' => $department]) }}" wire:navigate.hover data-reveal="{{ $i * 120 }}" class="group relative flex min-h-80 items-end overflow-hidden rounded-[2rem] p-8 sm:min-h-96 sm:p-10">
                <img decoding="async" src="{{ $image }}" alt="" loading="lazy" class="absolute inset-0 size-full object-cover transition duration-1000 group-hover:scale-105">
                <div class="absolute inset-0 {{ $i === 0 ? 'bg-gradient-to-t from-brand-950/90 to-brand-950/10' : 'bg-gradient-to-t from-zinc-950/90 to-zinc-950/10' }}"></div>
                <div class="relative">
                    <h2 class="text-3xl font-extrabold text-white sm:text-4xl">{{ $heading }}</h2>
                    <p class="mt-2 max-w-sm text-white/80">{{ $text }}</p>
                    <span class="mt-5 inline-flex items-center gap-2 rounded-full bg-white px-6 py-3 text-sm font-bold text-brand-950 transition group-hover:bg-amber-400">Shop {{ $department }} <flux:icon.arrow-right variant="micro" /></span>
                </div>
            </a>
        @endforeach
    </section>

    {{-- New arrivals scroller --}}
    @if ($this->newArrivals->isNotEmpty())
        <section class="mt-24" aria-labelledby="new-heading" x-data>
            <div class="container-shop flex items-end justify-between gap-4" data-reveal>
                <div>
                    <p class="text-sm font-bold tracking-widest text-amber-600 uppercase dark:text-amber-400">Just landed</p>
                    <h2 id="new-heading" class="mt-1 text-3xl font-extrabold tracking-tight text-brand-950 sm:text-4xl dark:text-white">New arrivals</h2>
                </div>
                <div class="flex gap-2">
                    <button type="button" x-on:click="$refs.track.scrollBy({ left: -$refs.track.clientWidth * 0.8, behavior: 'smooth' })" class="grid size-11 place-items-center rounded-full border transition hover:bg-brand-700 hover:text-white" aria-label="Scroll back"><flux:icon.arrow-left variant="micro" /></button>
                    <button type="button" x-on:click="$refs.track.scrollBy({ left: $refs.track.clientWidth * 0.8, behavior: 'smooth' })" class="grid size-11 place-items-center rounded-full border transition hover:bg-brand-700 hover:text-white" aria-label="Scroll forward"><flux:icon.arrow-right variant="micro" /></button>
                </div>
            </div>
            <div x-ref="track" class="no-scrollbar mt-8 flex snap-x snap-mandatory gap-4 overflow-x-auto scroll-smooth px-4 sm:gap-6 sm:px-6 lg:px-[max(2rem,calc((100vw-80rem)/2+2rem))]">
                @foreach ($this->newArrivals as $product)
                    <x-product-card :product="$product" :wishlisted="in_array($product->id, $this->wishlistIds)" class="w-[46%] shrink-0 snap-start sm:w-[30%] lg:w-[23%]" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Stats --}}
    <section class="container-shop mt-24" aria-label="Kimdern in numbers">
        <div class="grid grid-cols-2 gap-6 rounded-[2rem] bg-gradient-to-br from-brand-800 to-brand-950 p-8 text-white sm:p-12 lg:grid-cols-4">
            @foreach ([[2000, '+', 'Happy customers'], [37, '', 'States & FCT we deliver to'], [150, '+', 'Genuine products'], [48, 'h', 'Average Lagos delivery']] as $i => [$number, $suffix, $label])
                <div data-reveal="{{ $i * 100 }}" class="text-center">
                    <p class="text-4xl font-extrabold text-amber-400 sm:text-5xl"><span data-count="{{ $number }}" data-suffix="{{ $suffix }}">{{ number_format($number) }}{{ $suffix }}</span></p>
                    <p class="mt-2 text-sm text-white/75 sm:text-base">{{ $label }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Best sellers --}}
    @if ($this->bestSellers->isNotEmpty())
        <section class="container-shop mt-24" aria-labelledby="best-heading">
            <div class="flex items-end justify-between gap-4" data-reveal>
                <div>
                    <p class="text-sm font-bold tracking-widest text-amber-600 uppercase dark:text-amber-400">Customer favourites</p>
                    <h2 id="best-heading" class="mt-1 text-3xl font-extrabold tracking-tight text-brand-950 sm:text-4xl dark:text-white">Best sellers</h2>
                </div>
                <a href="{{ route('shop', ['sort' => 'popular']) }}" wire:navigate.hover class="hidden shrink-0 font-semibold text-brand-700 hover:underline sm:block dark:text-brand-400">See more →</a>
            </div>
            <div class="mt-8 grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 lg:grid-cols-4">
                @foreach ($this->bestSellers as $i => $product)
                    <x-product-card :product="$product" :wishlisted="in_array($product->id, $this->wishlistIds)" :delay="$i * 80" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Testimonials --}}
    @if ($this->testimonials->isNotEmpty())
        <section class="mt-24 bg-amber-50/70 py-20 dark:bg-zinc-900/60" aria-labelledby="reviews-heading">
            <div class="container-shop">
                <div class="text-center" data-reveal>
                    <p class="text-sm font-bold tracking-widest text-amber-600 uppercase dark:text-amber-400">Real reviews</p>
                    <h2 id="reviews-heading" class="mt-1 text-3xl font-extrabold tracking-tight text-brand-950 sm:text-4xl dark:text-white">Loved across Nigeria</h2>
                </div>
                <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($this->testimonials as $i => $review)
                        <figure data-reveal="{{ ($i % 3) * 100 }}" class="flex flex-col rounded-3xl bg-white p-7 shadow-sm ring-1 ring-zinc-900/5 transition hover:-translate-y-1 hover:shadow-xl dark:bg-zinc-950 dark:ring-white/10">
                            <x-stars :rating="$review->rating" />
                            <blockquote class="mt-4 flex-1 text-zinc-700 dark:text-zinc-300">
                                @if ($review->title)<p class="mb-2 font-bold text-zinc-900 dark:text-white">“{{ $review->title }}”</p>@endif
                                <p>{{ $review->body }}</p>
                            </blockquote>
                            <figcaption class="mt-6 flex items-center gap-3 border-t pt-5">
                                <span class="grid size-11 place-items-center rounded-full bg-brand-100 font-bold text-brand-800 dark:bg-brand-900 dark:text-brand-200">{{ \Illuminate\Support\Str::of($review->name)->substr(0, 1) }}</span>
                                <div class="min-w-0">
                                    <p class="font-semibold">{{ $review->name }} @if ($review->location)<span class="font-normal text-zinc-500">· {{ $review->location }}</span>@endif</p>
                                    @if ($review->product)
                                        <a href="{{ route('product', $review->product) }}" wire:navigate.hover class="block truncate text-xs text-brand-700 hover:underline dark:text-brand-400">{{ $review->product->name }}</a>
                                    @endif
                                </div>
                            </figcaption>
                        </figure>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>
