<x-mail::message>
# {{ $order->status->label() }}

Hello {{ \Illuminate\Support\Str::of($order->name)->before(' ') }},

{{ $order->status->description() }} This is about your order **{{ $order->number }}** ({{ naira($order->total) }}).

@if ($order->status === \App\Enums\OrderStatus::Shipped && $order->tracking_number)
**Tracking number:** {{ $order->tracking_number }}
@endif

@if ($note)
<x-mail::panel>
{{ $note }}
</x-mail::panel>
@endif

@include('mail.order-summary')

<x-mail::button :url="$order->trackingUrl()">
See order status
</x-mail::button>

@if ($order->status === \App\Enums\OrderStatus::Delivered)
We hope you love your items! If you have a moment, leave a review on the product page. It helps other shoppers a lot.
@endif

Thank you for shopping with us,<br>
{{ $store['store_name'] }}
</x-mail::message>
