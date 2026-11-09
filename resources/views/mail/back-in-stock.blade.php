<x-mail::message>
# Good news, it's back!

**{{ $alert->product->name }}**{{ $alert->variant ? ' ('.$alert->variant->label().')' : '' }} is back in stock at {{ $store['store_name'] }}.

Popular items sell out quickly, so grab yours while you can.

<x-mail::button :url="route('product', $alert->product)">
Shop it now
</x-mail::button>

You asked us to tell you when this item came back. We won't email you about it again.

With love,<br>
{{ $store['store_name'] }}
</x-mail::message>
