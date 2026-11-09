@props(['url'])
@php
    $store = \App\Models\Setting::values();
    // A logo on a local .test address can't be fetched by email apps, so the text logo is used until the site is live.
    $logo = app()->isLocal() ? null : \App\Models\Setting::logoUrl();
@endphp
<tr>
<td class="header">
<table class="header-band" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="center">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
@if ($logo)
<img src="{{ $logo }}" alt="{{ $store['store_name'] }}" height="44" style="height: 44px; max-height: 44px; width: auto;">
@else
<span class="wordmark">{{ $store['store_name'] }}<span class="wordmark-dot">.</span></span>
@endif
</a>
<p class="tagline">{{ $store['tagline'] }}</p>
</td>
</tr>
</table>
</td>
</tr>
