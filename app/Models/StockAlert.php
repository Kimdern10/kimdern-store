<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * "Email me when it's back in stock" for a product (or one size/colour of it).
 *
 * @property int $id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property string $email
 * @property Carbon|null $notified_at
 * @property-read Product $product
 * @property-read ProductVariant|null $variant
 */
#[Fillable(['product_id', 'product_variant_id', 'email', 'notified_at'])]
class StockAlert extends Model
{
    protected function casts(): array
    {
        return ['notified_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
