<x-mail::message>
# Payment sent for {{ $order->number }}

**{{ $order->name }}** says they have sent **{{ naira($order->total) }}** by bank transfer, with **{{ $order->number }}** as the narration.

Please check your bank app. When you see the money, confirm it so the customer gets their receipt and you can start packing.

<x-mail::button :url="route('admin.orders.show', $order)">
Confirm payment
</x-mail::button>

**Phone:** {{ $order->phone }}<br>
**Email:** {{ $order->email }}
</x-mail::message>
