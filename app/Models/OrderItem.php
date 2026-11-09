<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A line on an order. Name and price are copied in, so later product edits don't change past orders.
 *
 * @property int $id
 * @property int $order_id
 * @property int|null $product_id
 * @property int|null $product_variant_id
 * @property string $name
 * @property string|null $variant_label
 * @property string|null $image
 * @property int $price
 * @property int $quantity
 * @property int $line_total
 * @property-read Product|null $product
 */
#[Fillable(['order_id', 'product_id', 'product_variant_id', 'name', 'variant_label', 'image', 'price', 'quantity', 'line_total'])]
class OrderItem extends Model
{
    protected function casts(): array
    {
        return ['price' => 'integer', 'quantity' => 'integer', 'line_total' => 'integer'];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
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

    public function imageUrl(): string
    {
        return Media::url($this->image, true) ?? Media::placeholder();
    }
}
