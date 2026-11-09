{{-- Log in, sign up and password pages: a brand photo panel beside the form (the panel hides on phones). --}}
@php
    $store = \App\Models\Setting::values();
    $spotlight = \App\Models\Review::query()->where('is_approved', true)->where('rating', 5)->latest()->first();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <meta name="robots" content="noindex" />
    </head>
    <body class="min-h-screen bg-white font-sans text-zinc-800 antialiased dark:bg-zinc-950 dark:text-zinc-200">
        <div class="grid min-h-svh grid-cols-1 lg:grid-cols-[1.05fr_1fr]">
            {{-- Brand panel --}}
            <aside class="relative hidden overflow-hidden bg-brand-950 lg:block" aria-hidden="true">
                <img src="https://images.unsplash.com/photo-1483985988355-763728e1935b?w=1400&q=80&auto=format&fit=crop" alt="" class="absolute inset-0 size-full object-cover opacity-60">
                <div class="absolute inset-0 bg-gradient-to-br from-brand-950/95 via-brand-900/70 to-brand-950/40"></div>
                <div class="absolute -end-24 -top-24 size-96 rounded-full bg-amber-500/20 blur-3xl"></div>

                <div class="relative flex h-full flex-col justify-between p-12 text-white xl:p-16">
                    <x-store-logo light />

                    <div class="max-w-md">
                        <p class="text-sm font-bold tracking-widest text-amber-400 uppercase">{{ $store['store_name'] }}</p>
                        <h2 class="mt-3 text-4xl leading-tight font-extrabold xl:text-5xl">Beauty, hair & fashion you'll love.</h2>
                        <ul class="mt-8 space-y-4 text-white/85">
                            @foreach ([['truck', 'Delivery to all 36 states and Abuja'], ['shield-check', '100% genuine products, checked before dispatch'], ['bolt', 'Pay by transfer or card, confirmed in seconds'], ['heart', 'Save favourites and track every order']] as [$icon, $text])
                                <li class="flex items-center gap-3"><span class="grid size-9 shrink-0 place-items-center rounded-xl bg-white/10 backdrop-blur"><flux:icon :name="$icon" variant="mini" class="text-amber-300" /></span> {{ $text }}</li>
                            @endforeach
                        </ul>
                    </div>

                    @if ($spotlight)
                        <figure class="max-w-md rounded-3xl border border-white/15 bg-white/10 p-6 backdrop-blur-md">
                            <x-stars :rating="$spotlight->rating" />
                            <blockquote class="mt-3 text-white/90">“{{ \Illuminate\Support\Str::limit($spotlight->body, 140) }}”</blockquote>
                            <figcaption class="mt-3 text-sm text-white/70">{{ $spotlight->name }}@if ($spotlight->location) · {{ $spotlight->location }}@endif</figcaption>
                        </figure>
                    @else
                        <p class="text-sm text-white/60">© {{ date('Y') }} {{ $store['store_name'] }}</p>
                    @endif
                </div>
            </aside>

            {{-- Form side --}}
            <main class="relative flex min-w-0 flex-col bg-gradient-to-b from-brand-50/60 via-white to-white dark:from-brand-950/40 dark:via-zinc-950 dark:to-zinc-950">
                <div class="flex items-center justify-between p-5 sm:p-8">
                    <x-store-logo class="lg:invisible" />
                    <div class="flex items-center gap-2">
                        <button type="button" x-data x-on:click="$flux.dark = ! $flux.dark" class="grid size-10 place-items-center rounded-full text-zinc-500 transition hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Switch light or dark mode">
                            <flux:icon.moon variant="outline" class="size-5 dark:hidden" />
                            <flux:icon.sun variant="outline" class="hidden size-5 dark:block" />
                        </button>
                        <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 rounded-full border border-zinc-200 px-4 py-2 text-sm font-semibold transition hover:border-brand-500 hover:text-brand-700 dark:border-zinc-800 dark:hover:text-brand-400">
                            <flux:icon.arrow-left variant="micro" /> Back to shop
                        </a>
                    </div>
                </div>

                <div class="flex flex-1 items-center justify-center px-5 pb-10 sm:px-8">
                    <div class="w-full max-w-md">
                        {{ $slot }}
                    </div>
                </div>

                <p class="px-5 pb-6 text-center text-xs text-zinc-500 sm:px-8">
                    Protected by secure encryption. Read our <a href="{{ route('privacy') }}" class="font-semibold text-brand-700 hover:underline dark:text-brand-400">privacy policy</a>.
                </p>
            </main>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
