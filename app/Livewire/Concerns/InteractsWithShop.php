<?php

namespace App\Livewire\Concerns;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Cart;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;

/**
 * "Add to cart" and wishlist buttons for any page that shows product cards.
 */
trait InteractsWithShop
{
    public function addToCart(int $productId, ?int $variantId = null, int $quantity = 1): void
    {
        $product = Product::query()->active()->findOrFail($productId);
        $variant = $variantId ? ProductVariant::query()->where('product_id', $product->id)->find($variantId) : null;

        try {
            app(Cart::class)->add($product, $variant, $quantity);
        } catch (ValidationException $e) {
            Flux::toast(variant: 'danger', text: collect($e->errors())->flatten()->first());

            return;
        }

        $this->dispatch('cart-updated', open: true);
    }

    public function toggleWishlist(int $productId): void
    {
        $user = Auth::user();

        if (! $user) {
            session()->put('url.intended', url()->previous());
            $this->redirectRoute('login');

            return;
        }

        $result = $user->wishlist()->toggle([$productId]);
        unset($this->wishlistIds);

        Flux::toast(text: $result['attached'] !== [] ? 'Saved to your wishlist.' : 'Removed from your wishlist.');
    }

    /**
     * @return list<int>
     */
    #[Computed]
    public function wishlistIds(): array
    {
        return Auth::user()?->wishlist()->pluck('products.id')->map(fn ($id) => (int) $id)->all() ?? [];
    }
}
