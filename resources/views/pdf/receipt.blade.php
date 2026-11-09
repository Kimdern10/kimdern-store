<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $order->number }}</title>
    <style>
        @page { margin: 36px 40px; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1f2937; font-size: 11px; line-height: 1.5; }
        .brand { font-size: 22px; font-weight: bold; color: #064e3b; }
        .brand span { color: #d97706; }
        .muted { color: #6b7280; }
        .bar { height: 4px; background: #047857; margin: 14px 0 20px; }
        table { width: 100%; border-collapse: collapse; }
        .head td { vertical-align: top; }
        .title { font-size: 18px; font-weight: bold; color: #064e3b; text-align: right; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .paid { background: #d1fae5; color: #065f46; }
        .unpaid { background: #fef3c7; color: #92400e; }
        .items th { text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; border-bottom: 1px solid #e5e7eb; padding: 8px 6px; }
        .items td { padding: 9px 6px; border-bottom: 1px solid #f3f4f6; }
        .right { text-align: right; }
        .totals td { padding: 4px 6px; }
        .grand td { font-size: 14px; font-weight: bold; color: #064e3b; border-top: 2px solid #047857; padding-top: 8px; }
        .box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px 12px; }
        .footer { margin-top: 28px; text-align: center; font-size: 10px; color: #6b7280; }
    </style>
</head>
<body>
    <table class="head">
        <tr>
            <td>
                @if ($logo)
                    <img src="{{ $logo }}" alt="{{ $store['store_name'] }}" style="height: 40px;">
                @else
                    <div class="brand">{{ $store['store_name'] }}<span>.</span></div>
                @endif
                <div class="muted">{{ $store['address'] }}<br>{{ $store['phone'] }} · {{ $store['email'] }}</div>
            </td>
            <td>
                <div class="title">RECEIPT</div>
                <div class="right">
                    <strong>{{ $order->number }}</strong><br>
                    <span class="muted">{{ $order->created_at->timezone(config('app.timezone'))->format('j M Y, g:i a') }}</span><br>
                    <span class="badge {{ $order->paid_at ? 'paid' : 'unpaid' }}">{{ $order->paid_at ? 'PAID '.$order->paid_at->format('j M Y') : 'AWAITING PAYMENT' }}</span>
                </div>
            </td>
        </tr>
    </table>

    <div class="bar"></div>

    <table class="head" style="margin-bottom: 18px;">
        <tr>
            <td style="width: 50%; padding-right: 10px;">
                <div class="box">
                    <div class="muted" style="font-size: 9px; text-transform: uppercase; letter-spacing: 1px;">Bill & deliver to</div>
                    <strong>{{ $order->name }}</strong><br>
                    {{ $order->fullAddress() }}<br>
                    {{ $order->phone }} · {{ $order->email }}
                </div>
            </td>
            <td style="width: 50%; padding-left: 10px;">
                <div class="box">
                    <div class="muted" style="font-size: 9px; text-transform: uppercase; letter-spacing: 1px;">Payment</div>
                    <strong>{{ $order->paymentMethodLabel() }}</strong><br>
                    Status: {{ $order->status->label() }}<br>
                    @if ($order->payment_reference)
                        Ref: {{ $order->payment_reference }}
                    @elseif (! $order->paid_at)
                        {{ $store['bank_name'] }} · {{ $store['bank_account_number'] }}<br>{{ $store['bank_account_name'] }}
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Item</th>
                <th class="right">Price</th>
                <th class="right">Qty</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td><strong>{{ $item->name }}</strong>@if ($item->variant_label)<br><span class="muted">{{ $item->variant_label }}</span>@endif</td>
                    <td class="right">{{ naira($item->price) }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">{{ naira($item->line_total) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals" style="width: 45%; margin-left: 55%; margin-top: 10px;">
        <tr><td>Subtotal</td><td class="right">{{ naira($order->subtotal) }}</td></tr>
        @if ($order->discount > 0)
            <tr><td>Discount{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</td><td class="right">-{{ naira($order->discount) }}</td></tr>
        @endif
        <tr><td>Delivery ({{ $order->state }})</td><td class="right">{{ $order->delivery_fee > 0 ? naira($order->delivery_fee) : 'Free' }}</td></tr>
        <tr class="grand"><td>Total</td><td class="right">{{ naira($order->total) }}</td></tr>
    </table>

    <div class="footer">
        Thank you for shopping with {{ $store['store_name'] }}! Track your order any time at {{ route('orders.track') }}<br>
        Keep this receipt for returns and exchanges within 7 days of delivery.
    </div>
</body>
</html>
