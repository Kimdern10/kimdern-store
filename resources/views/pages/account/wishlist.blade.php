<?php

use App\Livewire\Concerns\InteractsWithShop;
use App\Models\Product;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('My wishlist')] class extends Component {
    use InteractsWithShop;

    public function mount(): void
    {
        Seo::set('My wishlist')->noindex = true;
    }

    /** @return Collection<int, Product> */
    #[Computed]
    public function products(): Collection
    {
        return Product::query()->forCards()->whereIn('id', $this->wishlistIds)->get();
    }
}; ?>

<div>
    <h2 class="text-xl font-bold">My wishlist</h2>

    @if ($this->products->isEmpty())
        <div class="mt-6 rounded-3xl border border-dashed p-10 text-center">
            <flux:icon.heart class="mx-auto size-10 text-zinc-300" />
            <p class="mt-3 font-semibold">Nothing saved yet</p>
            <p class="text-sm text-zinc-500">Tap the heart on any product to save it for later.</p>
            <flux:button variant="primary" class="mt-5" href="{{ route('shop') }}">Browse products</flux:button>
        </div>
    @else
        <div class="mt-6 grid grid-cols-2 gap-x-4 gap-y-10 sm:gap-x-6 xl:grid-cols-3">
            @foreach ($this->products as $product)
                <x-product-card :product="$product" :wishlisted="true" />
            @endforeach
        </div>
    @endif
</div>
