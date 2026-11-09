<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Paystack;
use App\Support\Receipt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

/**
 * Signed-link actions for an order: pay again and download the receipt.
 * The order page itself is the Livewire page pages::shop.order.
 */
class OrderController extends Controller
{
    /**
     * Start a new Paystack payment for an order that is still waiting.
     */
    public function pay(Order $order, Paystack $paystack): RedirectResponse
    {
        abort_unless($order->status === OrderStatus::Pending && $paystack->isConfigured(), 404);

        return redirect()->away($paystack->initialize($order, route('checkout.callback')));
    }

    public function receipt(Order $order): Response
    {
        return response(Receipt::pdf($order), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.Receipt::filename($order).'"',
        ]);
    }
}
