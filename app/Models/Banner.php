<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A slide in the home page hero.
 *
 * @property int $id
 * @property string|null $eyebrow
 * @property string $title
 * @property string|null $subtitle
 * @property string $image
 * @property string|null $button_text
 * @property string|null $link
 * @property int $sort_order
 * @property bool $is_active
 */
#[Fillable(['eyebrow', 'title', 'subtitle', 'image', 'button_text', 'link', 'sort_order', 'is_active'])]
class Banner extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function imageUrl(): string
    {
        return Media::url($this->image) ?? Media::placeholder();
    }
}
