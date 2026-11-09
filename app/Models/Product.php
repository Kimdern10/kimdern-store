<?php

namespace App\Models;

use App\Support\Media;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/**
 * @property int $id
 * @property int $category_id
 * @property string $name
 * @property string $slug
 * @property string|null $sku
 * @property string|null $brand
 * @property string|null $short_description
 * @property string|null $description
 * @property int $price
 * @property int|null $compare_at_price
 * @property int $stock
 * @property bool $is_active
 * @property bool $is_featured
 * @property int $sales_count
 * @property float $rating
 * @property int $reviews_count
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property-read Category $category
 * @property-read Collection<int, ProductImage> $images
 * @property-read Collection<int, ProductVariant> $variants
 * @property-read ProductImage|null $mainImage
 */
#[Fillable(['category_id', 'name', 'slug', 'sku', 'brand', 'short_description', 'description', 'price', 'compare_at_price', 'stock', 'is_active', 'is_featured', 'meta_title', 'meta_description'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'compare_at_price' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'rating' => 'float',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<ProductImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasOne<ProductImage, $this>
     */
    public function mainImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('id');
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /**
     * Products the shop window can show, with what the product card needs.
     *
     * @param  Builder<Product>  $query
     */
    public function scopeForCards(Builder $query): void
    {
        $query->active()->with(['mainImage', 'category:id,name,slug'])->withSum('variants as variants_stock', 'stock')->withCount('variants');
    }

    public function hasVariants(): bool
    {
        return $this->relationLoaded('variants')
            ? $this->variants->isNotEmpty()
            : (($this->variants_count ?? null) !== null ? $this->variants_count > 0 : $this->variants()->exists());
    }

    /**
     * Units available across the product (all variants added up when it has variants).
     */
    public function totalStock(): int
    {
        if (! $this->hasVariants()) {
            return $this->stock;
        }

        if ($this->relationLoaded('variants')) {
            return (int) $this->variants->sum('stock');
        }

        return (int) ($this->variants_stock ?? $this->variants()->sum('stock'));
    }

    public function inStock(): bool
    {
        return $this->totalStock() > 0;
    }

    public function isOnSale(): bool
    {
        return $this->compare_at_price !== null && $this->compare_at_price > $this->price;
    }

    public function discountPercent(): int
    {
        return $this->isOnSale() ? (int) round(100 - ($this->price / $this->compare_at_price * 100)) : 0;
    }

    public function imageUrl(bool $small = true): string
    {
        return Media::url($this->mainImage?->path, $small) ?? Media::placeholder();
    }

    public function isNew(): bool
    {
        return $this->created_at !== null && $this->created_at->gt(now()->subDays(30));
    }

    /**
     * Work the average star rating and review count out again from approved reviews.
     */
    public function refreshRating(): void
    {
        $approved = $this->reviews()->where('is_approved', true);

        $this->forceFill([
            'rating' => round((float) $approved->avg('rating'), 2),
            'reviews_count' => $approved->count(),
        ])->saveQuietly();
    }

    /**
     * @return Collection<int, string>
     */
    public function sizes(): Collection
    {
        return $this->variants->pluck('size')->filter()->unique()->values();
    }

    /**
     * @return Collection<string, string|null> colour name => hex
     */
    public function colours(): Collection
    {
        return $this->variants->filter(fn (ProductVariant $v) => filled($v->colour))->mapWithKeys(fn (ProductVariant $v) => [$v->colour => $v->colour_hex]);
    }
}
