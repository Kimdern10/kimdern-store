<?php

use App\Livewire\Concerns\InteractsWithShop;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Seo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The product listing: filters (category, price, size, colour, sale), sort and live search.
 * Filters are kept in the address bar so a filtered page can be shared or bookmarked.
 */
new #[Layout('layouts::shop')] class extends Component {
    use InteractsWithShop, WithPagination;

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: '')]
    public string $department = '';

    /** @var list<string> */
    #[Url(as: 'categories', except: [])]
    public array $selectedCategories = [];

    /** @var list<string> */
    #[Url(except: [])]
    public array $sizes = [];

    /** @var list<string> */
    #[Url(except: [])]
    public array $colours = [];

    #[Url(except: null)]
    public ?int $min = null;

    #[Url(except: null)]
    public ?int $max = null;

    #[Url(except: false)]
    public bool $sale = false;

    #[Url(except: false)]
    public bool $inStock = false;

    #[Url(except: 'featured')]
    public string $sort = 'featured';

    public ?Category $category = null;

    public function mount(?Category $category = null): void
    {
        if ($category?->exists) {
            abort_unless($category->is_active, 404);
            $this->category = $category;
            Seo::set($category->name, $category->description ?: "Shop {$category->name} at the best prices in Nigeria.", $category->imageUrl(false));
        } else {
            Seo::set($this->q !== '' ? 'Search: '.$this->q : 'Shop all products', 'Browse skincare, makeup, hair care, fragrance, clothing, shoes and bags. Fast delivery across Nigeria.');
        }
    }

    public function updated(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('q', 'department', 'selectedCategories', 'sizes', 'colours', 'min', 'max', 'sale', 'inStock', 'sort');
        $this->resetPage();
    }

    #[Computed]
    public function activeFilterCount(): int
    {
        return count($this->selectedCategories) + count($this->sizes) + count($this->colours)
            + ($this->min !== null ? 1 : 0) + ($this->max !== null ? 1 : 0) + ($this->sale ? 1 : 0) + ($this->inStock ? 1 : 0) + ($this->department !== '' ? 1 : 0);
    }

    /** @return Collection<int, Category> */
    #[Computed]
    public function allCategories(): Collection
    {
        return Category::query()->active()->get();
    }

    /** @return Collection<int, string> */
    #[Computed]
    public function sizeOptions(): Collection
    {
        $order = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];

        return $this->variantValues('size')->sortBy(fn (string $s) => [array_search($s, $order, true) === false ? 1 : 0, array_search($s, $order, true), (int) $s, $s])->values();
    }

    /** @return Collection<string, string|null> */
    #[Computed]
    public function colourOptions(): Collection
    {
        return ProductVariant::query()->whereNotNull('colour')
            ->whereHas('product', fn (Builder $q) => $q->active()->when($this->category, fn ($q) => $q->where('category_id', $this->category->id)))
            ->get(['colour', 'colour_hex'])->unique('colour')->sortBy('colour')->pluck('colour_hex', 'colour');
    }

    #[Computed]
    public function products(): LengthAwarePaginator
    {
        $query = Product::query()->forCards();

        if ($this->category) {
            $query->where('category_id', $this->category->id);
        } elseif ($this->selectedCategories !== []) {
            $query->whereHas('category', fn (Builder $q) => $q->whereIn('slug', $this->selectedCategories));
        }

        if ($this->department !== '' && ! $this->category) {
            $query->whereHas('category', fn (Builder $q) => $q->where('department', $this->department));
        }

        if (($term = trim($this->q)) !== '') {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
            $query->where(fn (Builder $q) => $q->where('name', 'like', $like)->orWhere('brand', 'like', $like)->orWhere('short_description', 'like', $like)->orWhereHas('category', fn (Builder $c) => $c->where('name', 'like', $like)));
        }

        if ($this->sizes !== [] || $this->colours !== []) {
            $query->whereHas('variants', function (Builder $v) {
                $v->where('stock', '>', 0)
                    ->when($this->sizes !== [], fn ($v) => $v->whereIn('size', $this->sizes))
                    ->when($this->colours !== [], fn ($v) => $v->whereIn('colour', $this->colours));
            });
        }

        if ($this->min !== null) {
            $query->where('price', '>=', $this->min * 100);
        }

        if ($this->max !== null) {
            $query->where('price', '<=', $this->max * 100);
        }

        if ($this->sale) {
            $query->whereNotNull('compare_at_price')->whereColumn('compare_at_price', '>', 'price');
        }

        if ($this->inStock) {
            $query->where(fn (Builder $q) => $q->where('stock', '>', 0)->orWhereHas('variants', fn ($v) => $v->where('stock', '>', 0)));
        }

        match ($this->sort) {
            'newest' => $query->latest(),
            'price-low' => $query->orderBy('price'),
            'price-high' => $query->orderByDesc('price'),
            'popular' => $query->orderByDesc('sales_count'),
            'rating' => $query->orderByDesc('rating')->orderByDesc('reviews_count'),
            default => $query->orderByDesc('is_featured')->orderByDesc('sales_count'),
        };

        return $query->orderBy('id')->paginate(12);
    }

    /** @return Collection<int, string> */
    private function variantValues(string $column): Collection
    {
        return ProductVariant::query()->whereNotNull($column)
            ->whereHas('product', fn (Builder $q) => $q->active()->when($this->category, fn ($q) => $q->where('category_id', $this->category->id)))
            ->distinct()->pluck($column);
    }
}; ?>

<div x-data="{ filters: false }">
    {{-- Page header --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-brand-50 via-white to-amber-50 dark:from-brand-950 dark:via-zinc-950 dark:to-zinc-900">
        <div class="container-shop py-10 sm:py-14">
            <nav class="text-sm text-zinc-500" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" wire:navigate.hover class="hover:text-brand-700">Home</a> <span class="mx-1">/</span>
                @if ($category)
                    <a href="{{ route('shop') }}" wire:navigate.hover class="hover:text-brand-700">Shop</a> <span class="mx-1">/</span> <span class="text-zinc-800 dark:text-zinc-200">{{ $category->name }}</span>
                @else
                    <span class="text-zinc-800 dark:text-zinc-200">Shop</span>
                @endif
            </nav>
            <h1 class="mt-3 text-4xl font-extrabold tracking-tight text-brand-950 sm:text-5xl dark:text-white">
                @if ($category)
                    {{ $category->name }}
                @elseif ($q !== '')
                    Results for “{{ $q }}”
                @elseif ($department !== '')
                    {{ \App\Models\Category::DEPARTMENTS[$department] ?? 'Shop' }}
                @else
                    Shop all
                @endif
            </h1>
            @if ($category?->description)
                <p class="mt-2 max-w-2xl text-zinc-600 dark:text-zinc-400">{{ $category->description }}</p>
            @endif

            @unless ($category)
                <div class="no-scrollbar mt-6 flex gap-2 overflow-x-auto pb-1">
                    @foreach ($this->allCategories as $cat)
                        <a href="{{ route('category', $cat) }}" wire:navigate.hover class="shrink-0 rounded-full border bg-white/70 px-4 py-2 text-sm font-semibold backdrop-blur transition hover:border-brand-600 hover:text-brand-700 dark:bg-zinc-900/70 dark:hover:text-brand-400">{{ $cat->name }}</a>
                    @endforeach
                </div>
            @endunless
        </div>
    </section>

    <div class="container-shop mt-8 lg:grid lg:grid-cols-[16rem_1fr] lg:gap-10">
        {{-- Filters --}}
        <aside>
            <div x-cloak x-show="filters" x-transition.opacity class="fixed inset-0 z-40 bg-zinc-950/50 lg:hidden" x-on:click="filters = false"></div>
            <div :class="filters ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="fixed inset-y-0 start-0 z-50 w-80 max-w-[85vw] -translate-x-full overflow-y-auto bg-white p-6 shadow-2xl transition duration-300 lg:sticky lg:top-28 lg:z-0 lg:max-h-[calc(100vh-8rem)] lg:w-auto lg:max-w-none lg:translate-x-0 lg:bg-transparent lg:p-0 lg:shadow-none dark:bg-zinc-900 lg:dark:bg-transparent">
                <div class="mb-6 flex items-center justify-between">
                    <h2 class="text-lg font-bold">Filters</h2>
                    <div class="flex items-center gap-2">
                        @if ($this->activeFilterCount > 0)
                            <button type="button" wire:click="clearFilters" class="text-sm font-semibold text-brand-700 hover:underline dark:text-brand-400">Clear all</button>
                        @endif
                        <button type="button" x-on:click="filters = false" class="grid size-9 place-items-center rounded-full hover:bg-zinc-100 lg:hidden dark:hover:bg-zinc-800" aria-label="Close filters"><flux:icon.x-mark /></button>
                    </div>
                </div>

                <div class="space-y-7">
                    <div>
                        <label for="catalog-search" class="text-sm font-bold">Search</label>
                        <div class="relative mt-2">
                            <flux:icon.magnifying-glass variant="micro" class="absolute start-3 top-1/2 -translate-y-1/2 text-zinc-400" />
                            <input id="catalog-search" type="search" wire:model.live.debounce.400ms="q" placeholder="Search products" class="h-11 w-full rounded-xl border bg-white ps-9 pe-3 text-sm outline-none focus:border-brand-600 focus:ring-2 focus:ring-brand-600/20 dark:bg-zinc-900">
                        </div>
                    </div>

                    @unless ($category)
                        <fieldset>
                            <legend class="text-sm font-bold">Category</legend>
                            <div class="mt-3 space-y-2">
                                @foreach ($this->allCategories as $cat)
                                    <label class="flex cursor-pointer items-center gap-3 text-sm">
                                        <input type="checkbox" wire:model.live="selectedCategories" value="{{ $cat->slug }}" class="size-4 rounded border-zinc-300 text-brand-700 focus:ring-brand-600">
                                        {{ $cat->name }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endunless

                    <fieldset>
                        <legend class="text-sm font-bold">Price (₦)</legend>
                        <div class="mt-3 flex items-center gap-2">
                            <input type="number" min="0" step="1000" wire:model.live.debounce.600ms="min" placeholder="Min" aria-label="Minimum price" class="h-10 w-full rounded-xl border bg-white px-3 text-sm outline-none focus:border-brand-600 dark:bg-zinc-900">
                            <span class="text-zinc-400">–</span>
                            <input type="number" min="0" step="1000" wire:model.live.debounce.600ms="max" placeholder="Max" aria-label="Maximum price" class="h-10 w-full rounded-xl border bg-white px-3 text-sm outline-none focus:border-brand-600 dark:bg-zinc-900">
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ([[null, 10000, 'Under ₦10k'], [10000, 30000, '₦10k–30k'], [30000, null, '₦30k+']] as [$from, $to, $label])
                                <button type="button" wire:click="$set('min', {{ $from ?? 'null' }}); $set('max', {{ $to ?? 'null' }})" @class(['rounded-full border px-3 py-1 text-xs font-semibold transition', 'border-brand-700 bg-brand-700 text-white' => $min === $from && $max === $to, 'hover:border-brand-600' => ! ($min === $from && $max === $to)])>{{ $label }}</button>
                            @endforeach
                        </div>
                    </fieldset>

                    @if ($this->sizeOptions->isNotEmpty())
                        <fieldset>
                            <legend class="text-sm font-bold">Size</legend>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($this->sizeOptions as $size)
                                    <label class="cursor-pointer">
                                        <input type="checkbox" wire:model.live="sizes" value="{{ $size }}" class="peer sr-only">
                                        <span class="grid h-9 min-w-11 place-items-center rounded-xl border px-2 text-sm font-semibold transition peer-checked:border-brand-700 peer-checked:bg-brand-700 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-brand-600 hover:border-brand-600">{{ $size }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif

                    @if ($this->colourOptions->isNotEmpty())
                        <fieldset>
                            <legend class="text-sm font-bold">Colour</legend>
                            <div class="mt-3 space-y-2">
                                @foreach ($this->colourOptions as $colour => $hex)
                                    <label class="flex cursor-pointer items-center gap-3 text-sm">
                                        <input type="checkbox" wire:model.live="colours" value="{{ $colour }}" class="size-4 rounded border-zinc-300 text-brand-700 focus:ring-brand-600">
                                        <span class="size-4 rounded-full ring-1 ring-zinc-900/15" style="background: {{ $hex ?: '#ccc' }}"></span>
                                        {{ $colour }}
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif

                    <fieldset class="space-y-3">
                        <legend class="mb-3 text-sm font-bold">More</legend>
                        <flux:switch wire:model.live="sale" label="On sale" align="left" />
                        <flux:switch wire:model.live="inStock" label="In stock only" align="left" />
                    </fieldset>
                </div>

                <flux:button variant="primary" class="mt-8 w-full lg:hidden" x-on:click="filters = false">Show {{ $this->products->total() }} products</flux:button>
            </div>
        </aside>

        {{-- Results --}}
        <section aria-label="Products" class="min-w-0">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <button type="button" x-on:click="filters = true" class="inline-flex h-10 items-center gap-2 rounded-full border px-4 text-sm font-semibold lg:hidden">
                        <flux:icon.adjustments-horizontal variant="micro" /> Filters
                        @if ($this->activeFilterCount > 0)
                            <span class="grid size-5 place-items-center rounded-full bg-brand-700 text-xs text-white">{{ $this->activeFilterCount }}</span>
                        @endif
                    </button>
                    <p class="text-sm text-zinc-500"><span class="font-semibold text-zinc-800 dark:text-zinc-200">{{ $this->products->total() }}</span> products</p>
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <span class="hidden text-zinc-500 sm:inline">Sort by</span>
                    <select wire:model.live="sort" class="h-10 rounded-full border bg-white ps-4 pe-9 text-sm font-semibold outline-none focus:border-brand-600 dark:bg-zinc-900">
                        <option value="featured">Featured</option>
                        <option value="newest">Newest</option>
                        <option value="popular">Best selling</option>
                        <option value="rating">Top rated</option>
                        <option value="price-low">Price: low to high</option>
                        <option value="price-high">Price: high to low</option>
                    </select>
                </label>
            </div>

            <div wire:loading.delay.class="opacity-50" class="transition-opacity">
                @if ($this->products->isEmpty())
                    <div class="rounded-3xl border border-dashed p-12 text-center">
                        <flux:icon.face-frown class="mx-auto size-12 text-zinc-300" />
                        <p class="mt-4 text-lg font-semibold">No products match these filters</p>
                        <p class="mt-1 text-sm text-zinc-500">Try removing a filter or searching for something else.</p>
                        <flux:button class="mt-6" wire:click="clearFilters">Clear filters</flux:button>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 xl:grid-cols-3">
                        @foreach ($this->products as $i => $product)
                            <x-product-card :product="$product" :wishlisted="in_array($product->id, $this->wishlistIds)" :delay="($i % 3) * 70" />
                        @endforeach
                    </div>

                    <div class="mt-12">
                        {{ $this->products->links() }}
                    </div>
                @endif
            </div>
        </section>
    </div>
</div>
