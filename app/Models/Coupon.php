<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Support\CustomerIdentity;
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
 * @property bool $first_order_only Only for people who have never had a paid order
 * @property bool $once_per_customer Each person can use it once
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property bool $is_active
 */
#[Fillable(['code', 'description', 'type', 'value', 'min_subtotal', 'max_uses', 'first_order_only', 'once_per_customer', 'starts_at', 'expires_at', 'is_active'])]
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
            'first_order_only' => 'boolean',
            'once_per_customer' => 'boolean',
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
     * Why this person can't use the code, or null when they can. A person is matched by their
     * email (cleaned up, so Gmail dots and "+extra" don't help), their phone number or their account.
     * Only paid orders count, so an unpaid or cancelled order doesn't use up the code.
     */
    public function problemForCustomer(?string $email = null, ?string $phone = null, ?int $userId = null): ?string
    {
        if (! $this->first_order_only && ! $this->once_per_customer) {
            return null;
        }

        $emailKey = CustomerIdentity::email($email);
        $phoneKey = CustomerIdentity::phone($phone);

        if (! $emailKey && ! $phoneKey && ! $userId) {
            return null;
        }

        $theirPaidOrders = Order::query()
            ->whereNotNull('paid_at')
            ->where('status', '!=', OrderStatus::Cancelled)
            ->where(function ($query) use ($emailKey, $phoneKey, $userId) {
                $query->whereRaw('1 = 0')
                    ->when($emailKey, fn ($q) => $q->orWhere('email_key', $emailKey))
                    ->when($phoneKey, fn ($q) => $q->orWhere('phone_key', $phoneKey))
                    ->when($userId, fn ($q) => $q->orWhere('user_id', $userId));
            });

        if ($this->first_order_only && (clone $theirPaidOrders)->exists()) {
            return "{$this->code} is for first orders only, and you've ordered with us before. Thank you for coming back!";
        }

        if ($this->once_per_customer && (clone $theirPaidOrders)->where('coupon_code', $this->code)->exists()) {
            return "You've already used {$this->code}. Each customer can use it once.";
        }

        return null;
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
