<?php

namespace App\Livewire\Concerns;

use App\Enums\OrderStatus;
use App\Mail\TransferReported;
use App\Models\Order;
use App\Models\Setting;
use App\Support\Mailer;
use Flux\Flux;

/**
 * The "I have sent the money" button on an order paid by direct bank transfer.
 * Tells the shop straight away so they can check their bank app and confirm.
 *
 * @property Order $order
 */
trait ReportsBankTransfer
{
    public function reportTransfer(): void
    {
        $order = $this->order;

        if ($order->status !== OrderStatus::Pending || $order->payment_method !== 'bank_transfer' || $order->transfer_reported_at !== null) {
            return;
        }

        $order->forceFill(['transfer_reported_at' => now()])->save();
        $order->history()->create(['status' => OrderStatus::Pending, 'note' => 'Customer says the bank transfer has been sent.']);

        Mailer::send((string) Setting::get('email'), new TransferReported($order));

        $order->load('history');
        Flux::toast(variant: 'success', text: 'Thank you! We are confirming your payment now.');
    }
}
