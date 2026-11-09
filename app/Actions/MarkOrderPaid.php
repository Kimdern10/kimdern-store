<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Mail\NewOrderAlert;
use App\Mail\OrderPlaced;
use App\Models\Order;
use App\Models\Setting;
use App\Support\Mailer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Records a successful Paystack payment. Safe to call twice (the callback and the
 * webhook usually both arrive): the second call changes nothing.
 */
class MarkOrderPaid
{
    /**
     * @param  array<string, mixed>  $transaction  The transaction as Paystack describes it.
     * @return bool Whether the order is now paid.
     */
    public function handle(Order $order, array $transaction): bool
    {
        if (($transaction['status'] ?? null) !== 'success') {
            return false;
        }

        if ((int) ($transaction['amount'] ?? 0) !== $order->total || strtoupper((string) ($transaction['currency'] ?? 'NGN')) !== 'NGN') {
            Log::warning('Paystack amount did not match the order.', ['order' => $order->number, 'amount' => $transaction['amount'] ?? null]);

            return false;
        }

        $newlyPaid = DB::transaction(function () use ($order): bool {
            $fresh = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($fresh->paid_at !== null || $fresh->status === OrderStatus::Cancelled) {
                return false;
            }

            $fresh->forceFill(['status' => OrderStatus::Paid, 'paid_at' => now()])->save();
            $fresh->history()->create(['status' => OrderStatus::Paid, 'note' => 'Paid online with Paystack.']);

            return true;
        });

        $order->refresh();

        if ($newlyPaid) {
            $order->load('items');
            Mailer::send($order->email, new OrderPlaced($order));
            Mailer::send((string) Setting::get('email'), new NewOrderAlert($order));
        }

        return $order->paid_at !== null;
    }
}
