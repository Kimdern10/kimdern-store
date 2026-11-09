@php($store = \App\Models\Setting::values())
<div class="grid gap-5 sm:grid-cols-2">
    @foreach ([
        ['phone', 'Call us', $store['phone'], 'tel:'.preg_replace('/\s+/', '', (string) $store['phone']), 'Mon – Sat, 8am – 7pm'],
        ['chat-bubble-left-right', 'WhatsApp', 'Chat with us', \App\Models\Setting::whatsappUrl('Hello Kimdern Store'), 'Fastest way to reach us'],
        ['envelope', 'Email', $store['email'], 'mailto:'.$store['email'], 'We reply within one working day'],
        ['map-pin', 'Visit', $store['address'], null, 'Pick-up by appointment'],
    ] as $i => [$icon, $title, $value, $link, $note])
        <div data-reveal="{{ $i * 80 }}" class="rounded-3xl border p-6 transition hover:-translate-y-1 hover:shadow-xl">
            <div class="grid size-12 place-items-center rounded-2xl bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-300"><flux:icon :name="$icon" /></div>
            <h2 class="mt-4 font-bold">{{ $title }}</h2>
            @if ($link)
                <a href="{{ $link }}" @if (str_starts_with($link, 'http')) target="_blank" rel="noopener" @endif class="mt-1 block break-words text-lg font-semibold text-brand-700 hover:underline dark:text-brand-400">{{ $value }}</a>
            @else
                <p class="mt-1 text-lg font-semibold">{{ $value }}</p>
            @endif
            <p class="mt-1 text-sm text-zinc-500">{{ $note }}</p>
        </div>
    @endforeach
</div>
<div class="mt-10 rounded-3xl bg-brand-900 p-8 text-center text-white">
    <h2 class="text-2xl font-bold">Questions about an order?</h2>
    <p class="mt-2 text-white/80">Have your order number ready (it starts with KDS-) so we can help you faster.</p>
    <a href="{{ route('orders.track') }}" class="mt-5 inline-flex rounded-full bg-amber-500 px-6 py-3 font-bold text-brand-950 hover:bg-amber-400">Track my order</a>
</div>
