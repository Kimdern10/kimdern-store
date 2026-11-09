<?php

namespace App\Enums;

/**
 * Where an order is on its way to the customer.
 */
enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting payment',
            self::Paid => 'Paid',
            self::Processing => 'Processing',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * A short sentence the customer sees for this step.
     */
    public function description(): string
    {
        return match ($this) {
            self::Pending => 'We have your order and are waiting for payment.',
            self::Paid => 'Payment received. Thank you!',
            self::Processing => 'We are packing your items.',
            self::Shipped => 'Your parcel is on its way.',
            self::Delivered => 'Your parcel has been delivered. Enjoy!',
            self::Cancelled => 'This order was cancelled.',
        };
    }

    /**
     * Flux badge colour for this status.
     */
    public function colour(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Paid => 'sky',
            self::Processing => 'indigo',
            self::Shipped => 'violet',
            self::Delivered => 'emerald',
            self::Cancelled => 'red',
        };
    }

    /**
     * The happy-path steps shown on the tracking timeline.
     *
     * @return list<self>
     */
    public static function timeline(): array
    {
        return [self::Pending, self::Paid, self::Processing, self::Shipped, self::Delivered];
    }

    public function step(): int
    {
        $index = array_search($this, self::timeline(), true);

        return $index === false ? -1 : $index;
    }

    /**
     * Whether the order has been paid for (or gone further).
     */
    public function isPaid(): bool
    {
        return in_array($this, [self::Paid, self::Processing, self::Shipped, self::Delivered], true);
    }
}
