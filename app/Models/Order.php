<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $number
 * @property int|null $user_id
 * @property string $email
 * @property string $name
 * @property string $phone
 * @property string $address
 * @property string $city
 * @property string $state
 * @property int|null $delivery_zone_id
 * @property string|null $delivery_eta
 * @property int $subtotal
 * @property int $discount
 * @property int $delivery_fee
 * @property int $total
 * @property string|null $coupon_code
 * @property OrderStatus $status
 * @property string $payment_method paystack|bank_transfer
 * @property string|null $payment_reference
 * @property Carbon|null $paid_at
 * @property string|null $tracking_number
 * @property string|null $notes
 * @property Carbon $created_at
 * @property-read Collection<int, OrderItem> $items
 * @property-read Collection<int, OrderStatusHistory> $history
 * @property-read User|null $user
 */
#[Fillable(['number', 'user_id', 'email', 'name', 'phone', 'address', 'city', 'state', 'delivery_zone_id', 'delivery_eta', 'subtotal', 'discount', 'delivery_fee', 'total', 'coupon_code', 'status', 'payment_method', 'payment_reference', 'paid_at', 'tracking_number', 'notes'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal' => 'integer',
            'discount' => 'integer',
            'delivery_fee' => 'integer',
            'total' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    public static function newNumber(): string
    {
        do {
            $number = 'KDS-'.Str::upper(Str::random(7));
        } while (static::query()->where('number', $number)->exists());

        return $number;
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<OrderStatusHistory, $this>
     */
    public function history(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at')->orderBy('id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<DeliveryZone, $this>
     */
    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class);
    }

    /**
     * A private link the customer (even a guest) can use to see and track this order.
     */
    public function trackingUrl(): string
    {
        return URL::signedRoute('orders.show', $this);
    }

    public function receiptUrl(): string
    {
        return URL::signedRoute('orders.receipt', $this);
    }

    public function itemCount(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function paymentMethodLabel(): string
    {
        return $this->payment_method === 'paystack' ? 'Card / bank (Paystack)' : 'Bank transfer';
    }

    public function fullAddress(): string
    {
        return "{$this->address}, {$this->city}, {$this->state}";
    }
}
