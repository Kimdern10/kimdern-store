<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Mail\OrderStatusChanged;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Mailer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Moves an order to a new status from the admin and emails the customer about it.
 * Cancelling puts the items back in stock.
 */
class UpdateOrderStatus
{
    public function handle(Order $order, OrderStatus $status, ?string $note = null, ?string $trackingNumber = null): void
    {
        if ($order->status === $status) {
            throw ValidationException::withMessages(['status' => 'The order is already '.strtolower($status->label()).'.']);
        }

        if ($order->status === OrderStatus::Cancelled || $order->status === OrderStatus::Delivered && $status === OrderStatus::Cancelled) {
            throw ValidationException::withMessages(['status' => 'A '.strtolower($order->status->label()).' order can\'t be changed to '.strtolower($status->label()).'.']);
        }

        DB::transaction(function () use ($order, $status, $note, $trackingNumber): void {
            if ($status === OrderStatus::Cancelled) {
                $this->restock($order);
            }

            $order->forceFill([
                'status' => $status,
                'paid_at' => $status->isPaid() ? ($order->paid_at ?? now()) : $order->paid_at,
                'tracking_number' => filled($trackingNumber) ? $trackingNumber : $order->tracking_number,
            ])->save();

            $order->history()->create(['status' => $status, 'note' => filled($note) ? $note : null]);
        });

        Mailer::send($order->email, new OrderStatusChanged($order->load('items'), $note));
    }

    private function restock(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->product_variant_id) {
                ProductVariant::query()->whereKey($item->product_variant_id)->increment('stock', $item->quantity);
            } elseif ($item->product_id) {
                Product::query()->whereKey($item->product_id)->increment('stock', $item->quantity);
            }
        }
    }
}
