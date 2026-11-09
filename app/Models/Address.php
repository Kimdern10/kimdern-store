<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $label
 * @property string $name
 * @property string $phone
 * @property string $address
 * @property string $city
 * @property string $state
 * @property bool $is_default
 */
#[Fillable(['user_id', 'label', 'name', 'phone', 'address', 'city', 'state', 'is_default'])]
class Address extends Model
{
    protected function casts(): array
    {
        return ['is_default' => 'boolean'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function oneLine(): string
    {
        return "{$this->address}, {$this->city}, {$this->state}";
    }
}
