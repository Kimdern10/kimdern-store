<?php

use App\Livewire\Concerns\AdminOnly;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\ImageOptimizer;
use App\Support\Media;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Add or edit a product: details, prices (typed in naira, saved in kobo), photos and sizes/colours.
 */
new #[Layout('layouts::admin'), Title('Product')] class extends Component {
    use AdminOnly, WithFileUploads;

    public ?Product $product = null;

    public string $name = '';

    public string $slug = '';

    public ?int $category_id = null;

    public string $brand = '';

    public string $sku = '';

    public string $short_description = '';

    public string $description = '';

    public string $price = '';

    public string $compare_at_price = '';

    public int $stock = 0;

    public bool $is_active = true;

    public bool $is_featured = false;

    public string $meta_title = '';

    public string $meta_description = '';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $photos = [];

    /** @var list<array{id: int|null, size: string, colour: string, colour_hex: string, price: string, stock: int|string}> */
    public array $variants = [];

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            $this->product = $product->load(['images', 'variants']);
            $this->fill($product->only('name', 'slug', 'category_id', 'is_active', 'is_featured', 'stock'));
            $this->brand = (string) $product->brand;
            $this->sku = (string) $product->sku;
            $this->short_description = (string) $product->short_description;
            $this->description = (string) $product->description;
            $this->meta_title = (string) $product->meta_title;
            $this->meta_description = (string) $product->meta_description;
            $this->price = (string) ($product->price / 100);
            $this->compare_at_price = $product->compare_at_price ? (string) ($product->compare_at_price / 100) : '';
            $this->variants = $product->variants->map(fn ($v) => [
                'id' => $v->id, 'size' => (string) $v->size, 'colour' => (string) $v->colour, 'colour_hex' => $v->colour_hex ?: '#000000',
                'price' => $v->price ? (string) ($v->price / 100) : '', 'stock' => $v->stock,
            ])->all();
        }
    }

    /** @return Collection<int, Category> */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->orderBy('name')->get();
    }

    public function updatedName(string $value): void
    {
        if (! $this->product) {
            $this->slug = Str::slug($value);
        }
    }

    public function addVariant(): void
    {
        $this->variants[] = ['id' => null, 'size' => '', 'colour' => '', 'colour_hex' => '#000000', 'price' => '', 'stock' => 0];
    }

    public function removeVariant(int $index): void
    {
        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
    }

    public function removePhoto(int $index): void
    {
        unset($this->photos[$index]);
        $this->photos = array_values($this->photos);
    }

    public function deleteImage(int $imageId): void
    {
        $image = $this->product?->images()->findOrFail($imageId);
        Media::delete($image->path);
        $image->delete();
        $this->product->load('images');
    }

    public function makeMainImage(int $imageId): void
    {
        foreach ($this->product->images as $image) {
            $image->update(['sort_order' => $image->id === $imageId ? 0 : $image->sort_order + 1]);
        }

        $this->product->load('images');
    }

    public function save(ImageOptimizer $optimizer): void
    {
        $this->slug = Str::slug($this->slug ?: $this->name);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:200', Rule::unique('products', 'slug')->ignore($this->product?->id)],
            'category_id' => ['required', 'exists:categories,id'],
            'brand' => ['nullable', 'string', 'max:100'],
            'sku' => ['nullable', 'string', 'max:60', Rule::unique('products', 'sku')->ignore($this->product?->id)],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:10000'],
            'price' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'compare_at_price' => ['nullable', 'numeric', 'gt:price'],
            'stock' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'meta_title' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'photos' => ['array', 'max:8'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'variants' => ['array', 'max:60'],
            'variants.*.size' => ['nullable', 'string', 'max:30'],
            'variants.*.colour' => ['nullable', 'string', 'max:40'],
            'variants.*.colour_hex' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
        ], [
            'compare_at_price.gt' => 'The "was" price must be higher than the selling price.',
            'photos.*.image' => 'Each photo must be an image (JPG, PNG or WebP).',
            'photos.*.max' => 'Each photo must be smaller than 8 MB.',
        ], ['category_id' => 'category', 'compare_at_price' => '"was" price', 'variants.*.stock' => 'stock']);

        foreach ($this->variants as $i => $row) {
            if (blank($row['size']) && blank($row['colour'])) {
                $this->addError("variants.{$i}.size", 'Give this option a size, a colour or both.');

                return;
            }
        }

        DB::transaction(function () use ($data, $optimizer) {
            $attributes = [
                'name' => $data['name'],
                'slug' => $data['slug'],
                'category_id' => $data['category_id'],
                'brand' => $data['brand'] ?: null,
                'sku' => $data['sku'] ?: null,
                'short_description' => $data['short_description'] ?: null,
                'description' => $data['description'] ?: null,
                'price' => (int) round((float) $data['price'] * 100),
                'compare_at_price' => filled($data['compare_at_price']) ? (int) round((float) $data['compare_at_price'] * 100) : null,
                'stock' => $this->variants === [] ? $data['stock'] : 0,
                'is_active' => $data['is_active'],
                'is_featured' => $data['is_featured'],
                'meta_title' => $data['meta_title'] ?: null,
                'meta_description' => $data['meta_description'] ?: null,
            ];

            $product = $this->product ? tap($this->product)->update($attributes) : Product::query()->create($attributes);

            $keep = collect($this->variants)->pluck('id')->filter()->all();
            $product->variants()->whereNotIn('id', $keep)->delete();

            foreach ($this->variants as $row) {
                $product->variants()->updateOrCreate(['id' => $row['id']], [
                    'size' => filled($row['size']) ? trim($row['size']) : null,
                    'colour' => filled($row['colour']) ? trim($row['colour']) : null,
                    'colour_hex' => filled($row['colour']) ? $row['colour_hex'] : null,
                    'price' => filled($row['price']) ? (int) round((float) $row['price'] * 100) : null,
                    'stock' => (int) $row['stock'],
                ]);
            }

            $next = (int) $product->images()->max('sort_order') + 1;

            foreach ($this->photos as $photo) {
                $product->images()->create(['path' => $optimizer->store($photo, 'products'), 'alt' => $product->name, 'sort_order' => $next++]);
            }

            $this->product = $product;
        });

        $this->photos = [];
        Flux::toast(variant: 'success', text: 'Product saved.');
        $this->redirectRoute('admin.products.edit', $this->product->id);
    }
}; ?>

<form wire:submit="save" class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.products') }}" class="text-sm font-semibold text-brand-700 hover:underline dark:text-brand-400">← Products</a>
            <flux:heading size="xl" class="mt-1">{{ $product ? 'Edit '.$product->name : 'Add a product' }}</flux:heading>
        </div>
        <div class="flex gap-2">
            @if ($product)
                <flux:button icon="eye" :href="route('product', $product)" target="_blank">View</flux:button>
            @endif
            <flux:button type="submit" variant="primary" icon="check">Save product</flux:button>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading>Details</flux:heading>
                <flux:input wire:model.blur="name" label="Product name" required />
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="slug" label="Web address" description:trailing="{{ url('product') }}/{{ $slug ?: '...' }}" />
                    <flux:input wire:model="sku" label="SKU (optional)" />
                </div>
                <flux:input wire:model="short_description" label="Short description" description="One or two sentences shown near the price." />
                <flux:textarea wire:model="description" label="Full description" rows="8" description="New paragraphs with a blank line. Start a line with - for a bullet point." />
            </section>

            <section class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div>
                    <flux:heading>Photos</flux:heading>
                    <flux:text>Upload up to 8 at a time. They are resized and turned into small WebP files automatically. The first photo is the main one.</flux:text>
                </div>

                @if ($product && $product->images->isNotEmpty())
                    <div class="grid grid-cols-3 gap-3 sm:grid-cols-5">
                        @foreach ($product->images as $i => $image)
                            <div wire:key="img-{{ $image->id }}" class="group relative aspect-square overflow-hidden rounded-xl bg-zinc-100 ring-2 {{ $i === 0 ? 'ring-brand-500' : 'ring-transparent' }}">
                                <img src="{{ $image->url(true) }}" alt="" class="size-full object-cover">
                                @if ($i === 0)<span class="absolute start-1 top-1 rounded bg-brand-600 px-1.5 text-[10px] font-bold text-white">Main</span>@endif
                                <div class="absolute inset-x-1 bottom-1 flex justify-between gap-1 opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100">
                                    @if ($i > 0)
                                        <button type="button" wire:click="makeMainImage({{ $image->id }})" class="rounded bg-white/90 px-1.5 py-0.5 text-[10px] font-semibold text-zinc-800">Make main</button>
                                    @endif
                                    <button type="button" wire:click="deleteImage({{ $image->id }})" wire:confirm="Delete this photo?" class="ms-auto rounded bg-red-600 px-1.5 py-0.5 text-[10px] font-semibold text-white">Delete</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <label class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-zinc-300 p-8 text-center transition hover:border-brand-500 hover:bg-brand-50/50 dark:border-zinc-700 dark:hover:bg-brand-950/30">
                    <flux:icon.photo class="size-8 text-zinc-400" />
                    <span class="font-semibold">Click to choose photos</span>
                    <span class="text-xs text-zinc-500">JPG, PNG or WebP, up to 8 MB each</span>
                    <input type="file" wire:model="photos" multiple accept="image/jpeg,image/png,image/webp" class="sr-only">
                </label>
                <div wire:loading wire:target="photos" class="text-sm text-zinc-500">Uploading…</div>
                @error('photos')<flux:text class="text-red-600">{{ $message }}</flux:text>@enderror
                @error('photos.*')<flux:text class="text-red-600">{{ $message }}</flux:text>@enderror

                @if ($photos)
                    <div class="grid grid-cols-3 gap-3 sm:grid-cols-5">
                        @foreach ($photos as $i => $photo)
                            <div wire:key="new-{{ $i }}" class="relative aspect-square overflow-hidden rounded-xl bg-zinc-100">
                                @if ($photo->isPreviewable())<img src="{{ $photo->temporaryUrl() }}" alt="" class="size-full object-cover">@endif
                                <span class="absolute start-1 top-1 rounded bg-amber-500 px-1.5 text-[10px] font-bold text-brand-950">New</span>
                                <button type="button" wire:click="removePhoto({{ $i }})" class="absolute end-1 top-1 grid size-6 place-items-center rounded-full bg-white/90 text-zinc-800" aria-label="Remove"><flux:icon.x-mark variant="micro" /></button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>

            <section class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <flux:heading>Sizes & colours</flux:heading>
                        <flux:text>Add a row for each size/colour you sell, with its own stock. Leave empty for a product with no options.</flux:text>
                    </div>
                    <flux:button size="sm" icon="plus" wire:click="addVariant">Add option</flux:button>
                </div>

                @if ($variants)
                    <div class="space-y-2">
                        <div class="hidden grid-cols-[1fr_1.4fr_1fr_0.8fr_auto] gap-2 px-1 text-xs font-semibold text-zinc-500 sm:grid">
                            <span>Size</span><span>Colour</span><span>Own price ₦ (optional)</span><span>Stock</span><span></span>
                        </div>
                        @foreach ($variants as $i => $row)
                            <div wire:key="variant-{{ $i }}-{{ $row['id'] ?? 'new' }}" class="grid grid-cols-2 gap-2 rounded-xl bg-zinc-50 p-2 sm:grid-cols-[1fr_1.4fr_1fr_0.8fr_auto] sm:bg-transparent sm:p-0 dark:bg-zinc-800/50 sm:dark:bg-transparent">
                                <flux:input wire:model="variants.{{ $i }}.size" placeholder="e.g. M, 42, 50ml" aria-label="Size" />
                                <div class="flex gap-1">
                                    <input type="color" wire:model="variants.{{ $i }}.colour_hex" class="h-10 w-11 shrink-0 cursor-pointer rounded-lg border border-zinc-200 bg-white p-1 dark:border-zinc-700 dark:bg-zinc-900" aria-label="Colour swatch">
                                    <flux:input wire:model="variants.{{ $i }}.colour" placeholder="e.g. Black" aria-label="Colour name" />
                                </div>
                                <flux:input wire:model="variants.{{ $i }}.price" type="number" step="0.01" min="0" placeholder="Same as product" aria-label="Price" />
                                <flux:input wire:model="variants.{{ $i }}.stock" type="number" min="0" aria-label="Stock" />
                                <flux:button variant="ghost" icon="trash" wire:click="removeVariant({{ $i }})" aria-label="Remove option" />
                                @error("variants.{$i}.size")<p class="col-span-full text-sm text-red-600">{{ $message }}</p>@enderror
                                @error("variants.{$i}.stock")<p class="col-span-full text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        </div>

        <div class="space-y-6">
            <section class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading>Visibility</flux:heading>
                <flux:switch wire:model="is_active" label="Show on the shop" />
                <flux:switch wire:model="is_featured" label="Feature on the home page" />
            </section>

            <section class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading>Price & stock</flux:heading>
                <flux:input wire:model="price" type="number" step="0.01" min="0" label="Selling price (₦)" required />
                <flux:input wire:model="compare_at_price" type="number" step="0.01" min="0" label="“Was” price (₦, optional)" description="Shows the item as on sale." />
                @if ($variants)
                    <flux:text>Stock is set for each size/colour below.</flux:text>
                @else
                    <flux:input wire:model="stock" type="number" min="0" label="Units in stock" />
                @endif
            </section>

            <section class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading>Organise</flux:heading>
                <flux:select wire:model="category_id" label="Category" required>
                    <flux:select.option value="">Choose a category</flux:select.option>
                    @foreach ($this->categories as $cat)
                        <flux:select.option :value="$cat->id">{{ $cat->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="brand" label="Brand (optional)" />
            </section>

            <section class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <flux:heading>Google & social sharing</flux:heading>
                <flux:input wire:model="meta_title" label="Page title (optional)" placeholder="{{ $name }}" />
                <flux:textarea wire:model="meta_description" label="Description (optional)" rows="3" placeholder="{{ $short_description }}" />
            </section>
        </div>
    </div>
</form>
