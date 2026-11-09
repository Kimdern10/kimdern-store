@php
    $store = \App\Models\Setting::values();
    $contact = array_filter([
        $store['phone'] ? "[{$store['phone']}](tel:".preg_replace('/\s+/', '', $store['phone']).')' : null,
        $store['email'] ? "[{$store['email']}](mailto:{$store['email']})" : null,
        \App\Models\Setting::whatsappUrl() ? '[WhatsApp]('.\App\Models\Setting::whatsappUrl().')' : null,
    ]);
    $links = array_filter([
        '[Shop online]('.route('home').')',
        $store['instagram_url'] ? "[Instagram]({$store['instagram_url']})" : null,
        $store['facebook_url'] ? "[Facebook]({$store['facebook_url']})" : null,
        $store['tiktok_url'] ? "[TikTok]({$store['tiktok_url']})" : null,
        $store['x_url'] ? "[X]({$store['x_url']})" : null,
    ]);
@endphp
<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="route('home')">
{{ $store['store_name'] }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
<p class="footer-name">{{ $store['store_name'] }}</p>

{{ $store['address'] }}

{{ implode(' · ', $contact) }}

{{ implode(' · ', $links) }}

© {{ date('Y') }} {{ $store['store_name'] }}. All rights reserved.
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
