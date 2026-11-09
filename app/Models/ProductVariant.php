<?php

namespace App\Models;

use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property string|null $size
 * @property string|null $colour
 * @property string|null $colour_hex
 * @property string|null $sku
 * @property int|null $price
 * @property int $stock
 * @property-read Product $product
 */
#[Fillable(['product_id', 'size', 'colour', 'colour_hex', 'sku', 'price', 'stock'])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['price' => 'integer', 'stock' => 'integer'];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * e.g. "Size M · Black"
     */
    public function label(): string
    {
        return collect([$this->size ? 'Size '.$this->size : null, $this->colour])->filter()->implode(' · ');
    }
}
