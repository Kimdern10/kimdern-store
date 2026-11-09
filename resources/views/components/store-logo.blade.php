@props(['light' => false])
@php
    $logo = \App\Models\Setting::logoUrl();
    $name = \App\Models\Setting::get('store_name');
@endphp
<a href="{{ route('home') }}" {{ $attributes->class('group flex shrink-0 items-center gap-2') }} aria-label="{{ $name }} home">
    @if ($logo)
        <img src="{{ $logo }}" alt="{{ $name }}" class="h-9 w-auto max-w-40 object-contain lg:h-10">
    @else
        <x-app-logo-icon class="size-9 transition duration-500 group-hover:rotate-[8deg]" />
        <span @class(['text-xl font-extrabold tracking-tight', 'text-white' => $light, 'text-brand-900 dark:text-white' => ! $light])>
            {{ \Illuminate\Support\Str::of($name)->before(' ') }}<span class="text-amber-500">.</span>
        </span>
    @endif
</a>
