<?php

namespace App\Models;

use App\Support\Media;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $department
 * @property string|null $description
 * @property string|null $image
 * @property int $sort_order
 * @property bool $is_active
 */
#[Fillable(['name', 'slug', 'department', 'description', 'image', 'sort_order', 'is_active'])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    public const array DEPARTMENTS = ['beauty' => 'Beauty', 'hair' => 'Hair', 'fashion' => 'Fashion'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * @param  Builder<Category>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public function imageUrl(bool $small = true): ?string
    {
        return Media::url($this->image, $small);
    }
}
