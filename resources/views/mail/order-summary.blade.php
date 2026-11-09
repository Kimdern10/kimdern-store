<x-mail::table>
| Item | Qty | Amount |
|:-----|:---:|-------:|
@foreach ($order->items as $item)
| {{ $item->name }}{{ $item->variant_label ? ' ('.$item->variant_label.')' : '' }} | {{ $item->quantity }} | {{ naira($item->line_total) }} |
@endforeach
| Subtotal | | {{ naira($order->subtotal) }} |
@if ($order->discount > 0)
| Discount{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }} | | −{{ naira($order->discount) }} |
@endif
| Delivery | | {{ $order->delivery_fee > 0 ? naira($order->delivery_fee) : 'Free' }} |
| **Total** | | **{{ naira($order->total) }}** |
</x-mail::table>
