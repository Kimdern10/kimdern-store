<?php

namespace App\Models;

use Database\Factories\ReviewFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_id
 * @property int|null $user_id
 * @property string $name
 * @property string|null $location
 * @property int $rating
 * @property string|null $title
 * @property string $body
 * @property bool $is_verified_buyer
 * @property bool $is_approved
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read Product $product
 */
#[Fillable(['product_id', 'user_id', 'name', 'location', 'rating', 'title', 'body', 'is_verified_buyer', 'is_approved'])]
class Review extends Model
{
    /** @use HasFactory<ReviewFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['rating' => 'integer', 'is_verified_buyer' => 'boolean', 'is_approved' => 'boolean'];
    }

    protected static function booted(): void
    {
        $refresh = fn (Review $review) => $review->product?->refreshRating();

        static::saved($refresh);
        static::deleted($refresh);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
