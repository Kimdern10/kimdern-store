{{-- A simple content page (contact, delivery, privacy). --}}
<x-layouts::shop :title="$heading">
    <section class="bg-gradient-to-br from-brand-50 via-white to-amber-50 dark:from-brand-950 dark:via-zinc-950 dark:to-zinc-900">
        <div class="container-shop max-w-4xl py-14 text-center">
            <p class="text-sm font-bold tracking-widest text-amber-600 uppercase dark:text-amber-400">{{ $eyebrow }}</p>
            <h1 class="mt-2 text-4xl font-extrabold tracking-tight text-brand-950 sm:text-5xl dark:text-white">{{ $heading }}</h1>
            @isset($intro)<p class="mx-auto mt-3 max-w-2xl text-zinc-600 dark:text-zinc-400">{{ $intro }}</p>@endisset
        </div>
    </section>
    <div class="container-shop max-w-4xl pt-12">
        @include($content)
    </div>
</x-layouts::shop>
