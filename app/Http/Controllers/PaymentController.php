<?php

namespace App\Http\Controllers;

use App\Actions\MarkOrderPaid;
use App\Models\Order;
use App\Services\Paystack;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /**
     * Paystack sends the customer back here after paying. We ask Paystack directly
     * whether the payment worked instead of trusting the address bar.
     */
    public function callback(Request $request, Paystack $paystack, MarkOrderPaid $markOrderPaid): RedirectResponse
    {
        $reference = (string) $request->query('reference', $request->query('trxref', ''));
        $order = $reference !== '' ? Order::query()->where('payment_reference', $reference)->first() : null;

        if (! $order) {
            return redirect()->route('home')->with('status', 'We could not find that payment.');
        }

        $transaction = $paystack->verify($reference);

        if ($transaction && $markOrderPaid->handle($order, $transaction)) {
            return redirect()->to($order->trackingUrl());
        }

        return redirect()->to($order->trackingUrl())->with('payment_failed', true);
    }

    /**
     * Paystack's server tells us about payments here, even if the customer closed their browser.
     */
    public function webhook(Request $request, Paystack $paystack, MarkOrderPaid $markOrderPaid): Response
    {
        $payload = $request->getContent();

        if (! $paystack->validSignature($payload, $request->header('x-paystack-signature'))) {
            return response('Invalid signature', 401);
        }

        $event = json_decode($payload, true);

        if (($event['event'] ?? null) !== 'charge.success') {
            return response('Ignored', 200);
        }

        $reference = (string) ($event['data']['reference'] ?? '');
        $order = Order::query()->where('payment_reference', $reference)->first();

        if (! $order) {
            Log::warning('Paystack webhook for an unknown reference.', ['reference' => $reference]);

            return response('Unknown order', 200);
        }

        // Double-check with Paystack rather than trusting the webhook body alone.
        $transaction = $paystack->verify($reference) ?? [];
        $markOrderPaid->handle($order, $transaction);

        return response('OK', 200);
    }
}
