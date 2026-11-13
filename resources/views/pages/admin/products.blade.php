<?php

use App\Livewire\Concerns\AdminOnly;
use App\Models\Category;
use App\Models\Product;
use App\Support\Media;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin'), Title('Products')] class extends Component {
    use AdminOnly, WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $category = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    /** @return Collection<int, Category> */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->orderBy('name')->get();
    }

    #[Computed]
    public function products(): LengthAwarePaginator
    {
        return Product::query()->with(['mainImage', 'category:id,name'])->withSum('variants as variants_stock', 'stock')->withCount('variants')
            ->when($this->category !== '', fn ($q) => $q->where('category_id', $this->category))
            ->when(trim($this->search) !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.trim($this->search).'%')->orWhere('sku', 'like', '%'.trim($this->search).'%')))
            ->latest()
            ->paginate(20);
    }

    public function toggleActive(int $id): void
    {
        $product = Product::query()->findOrFail($id);
        $product->update(['is_active' => ! $product->is_active]);
        Flux::toast(text: $product->is_active ? "{$product->name} is now on the shop." : "{$product->name} is hidden from the shop.");
    }

    public function delete(int $id): void
    {
        $product = Product::query()->with('images')->findOrFail($id);

        foreach ($product->images as $image) {
            Media::delete($image->path);
        }

        $product->delete();
        Flux::toast(text: 'Product deleted.');
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <flux:heading size="xl">Products</flux:heading>
        <flux:button variant="primary" icon="plus" :href="route('admin.products.create')" wire:navigate.hover>Add product</flux:button>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row">
        <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass" placeholder="Search name or SKU" class="sm:max-w-sm" />
        <flux:select wire:model.live="category" class="sm:max-w-xs">
            <flux:select.option value="">All categories</flux:select.option>
            @foreach ($this->categories as $cat)
                <flux:select.option :value="$cat->id">{{ $cat->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead class="border-b border-zinc-100 bg-zinc-50 text-xs tracking-wide text-zinc-500 uppercase dark:border-zinc-800 dark:bg-zinc-950/40">
                <tr><th class="px-5 py-3 text-start">Product</th><th class="px-5 py-3 text-start">Price</th><th class="px-5 py-3 text-start">Stock</th><th class="px-5 py-3 text-start">On shop</th><th class="px-5 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->products as $product)
                    <tr wire:key="product-{{ $product->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <img src="{{ $product->imageUrl() }}" alt="" class="size-12 rounded-xl object-cover">
                                <div class="min-w-0">
                                    <a href="{{ route('admin.products.edit', $product->id) }}" class="font-semibold hover:underline">{{ $product->name }}</a>
                                    <p class="text-xs text-zinc-500">{{ $product->category?->name }} · {{ $product->sku }} @if ($product->is_featured)· <span class="text-amber-600">Featured</span>@endif</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3 font-semibold">{{ naira($product->price) }}@if ($product->isOnSale())<p class="text-xs font-normal text-zinc-400 line-through">{{ naira($product->compare_at_price) }}</p>@endif</td>
                        <td class="px-5 py-3">
                            @php($stock = $product->totalStock())
                            <flux:badge size="sm" :color="$stock === 0 ? 'red' : ($stock <= 5 ? 'amber' : 'emerald')">{{ $stock }}</flux:badge>
                            @if ($product->variants_count)<p class="mt-1 text-xs text-zinc-500">{{ $product->variants_count }} options</p>@endif
                        </td>
                        <td class="px-5 py-3"><flux:switch :checked="$product->is_active" wire:click="toggleActive({{ $product->id }})" aria-label="Show on shop" /></td>
                        <td class="px-5 py-3 text-end">
                            <flux:dropdown align="end">
                                <flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" aria-label="Actions" />
                                <flux:menu>
                                    <flux:menu.item icon="pencil-square" :href="route('admin.products.edit', $product->id)">Edit</flux:menu.item>
                                    <flux:menu.item icon="eye" :href="route('product', $product)" target="_blank">View on shop</flux:menu.item>
                                    <flux:menu.separator />
                                    <flux:menu.item icon="trash" variant="danger" wire:click="delete({{ $product->id }})" wire:confirm="Delete {{ $product->name }}? Past orders keep their details.">Delete</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-zinc-500">No products yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->products->links() }}
</div>
