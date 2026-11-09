<?php

namespace App\Models;

use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string|null $description
 * @property string $type percent|fixed
 * @property int $value percent (1–100) or kobo
 * @property int $min_subtotal
 * @property int|null $max_uses
 * @property int $used_count
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property bool $is_active
 */
#[Fillable(['code', 'description', 'type', 'value', 'min_subtotal', 'max_uses', 'starts_at', 'expires_at', 'is_active'])]
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'min_subtotal' => 'integer',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Codes are stored in capitals so customers can type them any way.
     *
     * @return Attribute<string, string>
     */
    protected function code(): Attribute
    {
        return Attribute::make(set: fn (string $value) => strtoupper(trim($value)));
    }

    public static function findByCode(?string $code): ?self
    {
        return blank($code) ? null : static::query()->where('code', strtoupper(trim($code)))->first();
    }

    /**
     * Why this coupon can't be used on a cart of this size, or null when it can.
     */
    public function problemWith(int $subtotal): ?string
    {
        return match (true) {
            ! $this->is_active => 'This code is not active.',
            $this->starts_at !== null && $this->starts_at->isFuture() => 'This code is not active yet.',
            $this->expires_at !== null && $this->expires_at->isPast() => 'This code has expired.',
            $this->max_uses !== null && $this->used_count >= $this->max_uses => 'This code has been fully used.',
            $subtotal < $this->min_subtotal => 'Spend at least '.naira($this->min_subtotal).' to use this code.',
            default => null,
        };
    }

    /**
     * The amount taken off a cart of this size, in kobo.
     */
    public function discountFor(int $subtotal): int
    {
        $discount = $this->type === 'percent'
            ? (int) round($subtotal * min($this->value, 100) / 100)
            : $this->value;

        return min($discount, $subtotal);
    }

    public function summary(): string
    {
        return $this->type === 'percent' ? $this->value.'% off' : naira($this->value).' off';
    }
}
