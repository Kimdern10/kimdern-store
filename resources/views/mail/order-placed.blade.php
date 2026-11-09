<x-mail::message>
# Thank you, {{ \Illuminate\Support\Str::of($order->name)->before(' ') }}!

@if ($order->paid_at)
We have received your payment for order **{{ $order->number }}**. We will start packing your items right away.
@else
We have received your order **{{ $order->number }}**. To confirm it, please pay **{{ naira($order->total) }}** by bank transfer:

<x-mail::panel>
**Bank:** {{ $store['bank_name'] }}<br>
**Account name:** {{ $store['bank_account_name'] }}<br>
**Account number:** {{ $store['bank_account_number'] }}<br>
**Amount:** {{ naira($order->total) }}<br>
**Reference:** {{ $order->number }}
</x-mail::panel>

Please use **{{ $order->number }}** as the transfer narration. Once we see your payment we will email you and start packing.
@endif

@include('mail.order-summary')

**Delivering to:** {{ $order->name }}, {{ $order->fullAddress() }} ({{ $order->phone }})
@if ($order->delivery_eta)
<br>**Expected delivery:** {{ $order->delivery_eta }} after payment
@endif

<x-mail::button :url="$order->trackingUrl()">
Track my order
</x-mail::button>

Your receipt is attached as a PDF. Questions? Just reply to this email or chat with us on WhatsApp.

With love,<br>
{{ $store['store_name'] }}
</x-mail::message>
