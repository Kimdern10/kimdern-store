<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;

/**
 * One product (or one size/colour of a product) in the cart.
 */
final readonly class CartLine
{
    public function __construct(
        public string $key,
        public Product $product,
        public ?ProductVariant $variant,
        public int $quantity,
    ) {}

    public function unitPrice(): int
    {
        return $this->variant->price ?? $this->product->price;
    }

    public function total(): int
    {
        return $this->unitPrice() * $this->quantity;
    }

    public function available(): int
    {
        return $this->variant ? $this->variant->stock : $this->product->stock;
    }

    public function variantLabel(): ?string
    {
        return $this->variant?->label() ?: null;
    }
}
