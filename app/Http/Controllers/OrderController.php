<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\Paystack;
use App\Support\Receipt;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class OrderController extends Controller
{
    /**
     * The order page customers (and guests) reach through the signed link in their emails.
     */
    public function show(Request $request, Order $order): View
    {
        $order->load(['items.product', 'history']);
        Seo::set('Order '.$order->number)->noindex = true;

        $retryUrl = $order->status === OrderStatus::Pending && $order->payment_method === 'paystack'
            ? URL::signedRoute('orders.pay', $order)
            : null;

        return view('shop.order', [
            'order' => $order,
            'justPlaced' => $order->created_at->gt(now()->subMinutes(30)) && ! session('payment_failed'),
            'retryUrl' => $retryUrl,
        ]);
    }

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

    /**
     * "Track my order": find an order by its number and the email used to place it.
     */
    public function track(Request $request): View|RedirectResponse
    {
        Seo::set('Track your order', 'Enter your order number and email to see where your Kimdern Store order is.');

        if (! $request->filled('number')) {
            return view('shop.track');
        }

        $data = $request->validate([
            'number' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email'],
        ]);

        $key = 'track-order:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            return back()->withInput()->withErrors(['number' => 'Too many tries. Please wait a minute and try again.']);
        }

        RateLimiter::hit($key, 60);

        $order = Order::query()->where('number', strtoupper(trim($data['number'])))->where('email', strtolower(trim($data['email'])))->first();

        if (! $order) {
            return back()->withInput()->withErrors(['number' => "We couldn't find an order with that number and email."]);
        }

        return redirect()->to($order->trackingUrl());
    }
}
