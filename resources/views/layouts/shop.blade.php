@props(['title' => null])
@php
    $store = \App\Models\Setting::values();
    $seo = app(\App\Support\Seo::class);
    $navCategories = \App\Models\Category::query()->active()->get(['id', 'name', 'slug', 'department']);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{{ $seo->fullTitle($title) }}</title>
        <meta name="description" content="{{ $seo->metaDescription() }}" />
        <link rel="canonical" href="{{ url()->current() }}" />
        @if ($seo->noindex)
            <meta name="robots" content="noindex, nofollow" />
        @endif

        <meta property="og:type" content="{{ $seo->type }}" />
        <meta property="og:site_name" content="{{ $store['store_name'] }}" />
        <meta property="og:title" content="{{ $seo->fullTitle($title) }}" />
        <meta property="og:description" content="{{ $seo->metaDescription() }}" />
        <meta property="og:url" content="{{ url()->current() }}" />
        <meta property="og:image" content="{{ $seo->shareImage() }}" />
        <meta property="og:locale" content="en_NG" />
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="theme-color" content="#064e3b" />

        <link rel="icon" href="/favicon.ico" sizes="any" />
        <link rel="icon" href="/favicon.svg" type="image/svg+xml" />
        <link rel="apple-touch-icon" href="/apple-touch-icon.png" />

        {{ $head ?? '' }}

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="min-h-screen bg-white font-sans text-zinc-800 antialiased selection:bg-amber-200 selection:text-zinc-900 dark:bg-zinc-950 dark:text-zinc-200">
        <a href="#main" class="sr-only z-[100] rounded-full bg-brand-700 px-4 py-2 text-white focus:not-sr-only focus:fixed focus:top-3 focus:left-3">Skip to content</a>

        {{-- Announcement bar --}}
        @if (filled($store['announcement']))
            <div class="bg-brand-900 text-brand-50 dark:bg-brand-950">
                <div class="container-shop flex items-center justify-center gap-2 py-2 text-center text-xs font-medium sm:text-sm">
                    <flux:icon.truck variant="micro" class="text-amber-400" />
                    <span>{{ $store['announcement'] }}</span>
                </div>
            </div>
        @endif

        {{-- Header --}}
        <header data-header x-data="{ menu: false }" class="sticky top-0 z-40 border-b border-transparent bg-white/85 backdrop-blur-xl transition-shadow duration-300 [&.is-scrolled]:border-zinc-200 [&.is-scrolled]:shadow-lg [&.is-scrolled]:shadow-zinc-900/5 dark:bg-zinc-950/85 dark:[&.is-scrolled]:border-zinc-800">
            <div class="container-shop flex h-16 items-center gap-3 lg:h-20 lg:gap-6">
                <button type="button" class="-ms-2 grid size-10 place-items-center rounded-full hover:bg-zinc-100 lg:hidden dark:hover:bg-zinc-800" x-on:click="menu = true" aria-label="Open menu">
                    <flux:icon.bars-3 />
                </button>

                <x-store-logo />

                <nav class="ms-4 hidden items-center gap-1 text-sm font-semibold lg:flex" aria-label="Main">
                    <a href="{{ route('shop') }}" class="rounded-full px-3 py-2 transition hover:bg-zinc-100 hover:text-brand-700 dark:hover:bg-zinc-800 dark:hover:text-brand-400">Shop all</a>
                    @foreach (\App\Models\Category::DEPARTMENTS as $key => $label)
                        <div class="group relative">
                            <a href="{{ route('shop', ['department' => $key]) }}" class="flex items-center gap-1 rounded-full px-3 py-2 transition hover:bg-zinc-100 hover:text-brand-700 dark:hover:bg-zinc-800 dark:hover:text-brand-400">
                                {{ $label }} <flux:icon.chevron-down variant="micro" class="opacity-60 transition group-hover:rotate-180" />
                            </a>
                            <div class="invisible absolute start-0 top-full w-56 translate-y-2 pt-2 opacity-0 transition duration-200 group-focus-within:visible group-focus-within:translate-y-0 group-focus-within:opacity-100 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">
                                <div class="rounded-2xl border border-zinc-200 bg-white p-2 shadow-xl dark:border-zinc-800 dark:bg-zinc-900">
                                    @foreach ($navCategories->where('department', $key) as $category)
                                        <a href="{{ route('category', $category) }}" class="block rounded-xl px-3 py-2 font-medium hover:bg-brand-50 hover:text-brand-800 dark:hover:bg-brand-950 dark:hover:text-brand-300">{{ $category->name }}</a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <a href="{{ route('shop', ['sort' => 'newest']) }}" class="rounded-full px-3 py-2 transition hover:bg-zinc-100 hover:text-brand-700 dark:hover:bg-zinc-800 dark:hover:text-brand-400">New in</a>
                    <a href="{{ route('shop', ['sale' => 1]) }}" class="rounded-full px-3 py-2 text-amber-600 transition hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-950/40">Sale</a>
                </nav>

                <div class="ms-auto flex items-center gap-1 sm:gap-2">
                    <livewire:search-box />

                    <button type="button" x-on:click="$flux.dark = ! $flux.dark" class="grid size-10 place-items-center rounded-full transition hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Switch light or dark mode">
                        <flux:icon.moon variant="outline" class="size-5 dark:hidden" />
                        <flux:icon.sun variant="outline" class="hidden size-5 dark:block" />
                    </button>

                    @auth
                        <a href="{{ route('account.wishlist') }}" class="hidden size-10 place-items-center rounded-full transition hover:bg-zinc-100 sm:grid dark:hover:bg-zinc-800" aria-label="Wishlist">
                            <flux:icon.heart variant="outline" class="size-5" />
                        </a>
                    @endauth

                    <a href="{{ auth()->check() ? (auth()->user()->is_admin ? route('admin.dashboard') : route('account.orders')) : route('login') }}" class="grid size-10 place-items-center rounded-full transition hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="{{ auth()->check() ? 'My account' : 'Log in' }}">
                        <flux:icon.user variant="outline" class="size-5" />
                    </a>

                    <livewire:cart-drawer />
                </div>
            </div>

            {{-- Mobile menu --}}
            <div x-cloak x-show="menu" class="fixed inset-0 z-50 lg:hidden" x-on:keydown.escape.window="menu = false">
                <div x-show="menu" x-transition.opacity class="absolute inset-0 bg-zinc-950/50 backdrop-blur-sm" x-on:click="menu = false"></div>
                <nav x-show="menu" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="-translate-x-full" x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="-translate-x-full" class="absolute inset-y-0 start-0 flex w-80 max-w-[85vw] flex-col overflow-y-auto bg-white p-5 shadow-2xl dark:bg-zinc-900" aria-label="Mobile">
                    <div class="mb-6 flex items-center justify-between">
                        <x-store-logo />
                        <button type="button" class="grid size-10 place-items-center rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800" x-on:click="menu = false" aria-label="Close menu">
                            <flux:icon.x-mark />
                        </button>
                    </div>
                    <a href="{{ route('shop') }}" class="rounded-xl px-3 py-3 font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800">Shop all</a>
                    @foreach (\App\Models\Category::DEPARTMENTS as $key => $label)
                        <p class="mt-4 px-3 text-xs font-bold tracking-widest text-zinc-400 uppercase">{{ $label }}</p>
                        @foreach ($navCategories->where('department', $key) as $category)
                            <a href="{{ route('category', $category) }}" class="rounded-xl px-3 py-2.5 font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ $category->name }}</a>
                        @endforeach
                    @endforeach
                    <div class="my-4 border-t"></div>
                    <a href="{{ route('shop', ['sort' => 'newest']) }}" class="rounded-xl px-3 py-2.5 font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800">New in</a>
                    <a href="{{ route('shop', ['sale' => 1]) }}" class="rounded-xl px-3 py-2.5 font-medium text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-zinc-800">Sale</a>
                    <a href="{{ route('orders.track') }}" class="rounded-xl px-3 py-2.5 font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800">Track my order</a>
                    <a href="{{ route('contact') }}" class="rounded-xl px-3 py-2.5 font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800">Contact us</a>
                    @auth
                        <a href="{{ route('account.wishlist') }}" class="rounded-xl px-3 py-2.5 font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800">My wishlist</a>
                    @endauth
                </nav>
            </div>
        </header>

        <main id="main">
            {{ $slot }}
        </main>

        @include('partials.shop-footer', ['store' => $store, 'navCategories' => $navCategories])

        @include('partials.cookie-notice')

        @persist('toast')
            <flux:toast.group position="bottom end">
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
