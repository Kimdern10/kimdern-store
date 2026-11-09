<x-mail::message>
# New order {{ $order->number }}

**{{ $order->name }}** ordered {{ $order->items->sum('quantity') }} item(s) for **{{ naira($order->total) }}**.

**Payment:** {{ $order->paymentMethodLabel() }} — {{ $order->paid_at ? 'paid' : 'waiting for transfer' }}<br>
**Phone:** {{ $order->phone }}<br>
**Email:** {{ $order->email }}<br>
**Deliver to:** {{ $order->fullAddress() }}

@include('mail.order-summary')

<x-mail::button :url="route('admin.orders.show', $order)">
Open the order
</x-mail::button>
</x-mail::message>
