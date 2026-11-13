@props(['title' => null])
@php
    $store = \App\Models\Setting::values();
    $seo = app(\App\Support\Seo::class);
    $navCategories = \App\Models\Category::forMenu();
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

        <link rel="icon" href="/favicon.ico?v=2" sizes="any" />
        <link rel="icon" href="/favicon.svg?v=2" type="image/svg+xml" />
        <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=2" />
        <link rel="manifest" href="/site.webmanifest" />

        {{ $head ?? '' }}

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @include('partials.appearance-default')
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
                    <a href="{{ route('home') }}" wire:navigate.hover @class(['flex items-center gap-1.5 rounded-full px-3 py-2 transition hover:bg-zinc-100 hover:text-brand-700 dark:hover:bg-zinc-800 dark:hover:text-brand-400', 'bg-brand-50 text-brand-800 dark:bg-brand-950 dark:text-brand-300' => request()->routeIs('home')])>
                        <flux:icon.home variant="micro" /> Home
                    </a>
                    <a href="{{ route('shop') }}" wire:navigate.hover @class(['rounded-full px-3 py-2 transition hover:bg-zinc-100 hover:text-brand-700 dark:hover:bg-zinc-800 dark:hover:text-brand-400', 'bg-brand-50 text-brand-800 dark:bg-brand-950 dark:text-brand-300' => request()->routeIs('shop')])>Shop all</a>
                    @foreach (\App\Models\Category::DEPARTMENTS as $key => $label)
                        <div class="group relative">
                            <a href="{{ route('shop', ['department' => $key]) }}" wire:navigate.hover class="flex items-center gap-1 rounded-full px-3 py-2 transition hover:bg-zinc-100 hover:text-brand-700 dark:hover:bg-zinc-800 dark:hover:text-brand-400">
                                {{ $label }} <flux:icon.chevron-down variant="micro" class="opacity-60 transition group-hover:rotate-180" />
                            </a>
                            <div class="invisible absolute start-0 top-full w-56 translate-y-2 pt-2 opacity-0 transition duration-200 group-focus-within:visible group-focus-within:translate-y-0 group-focus-within:opacity-100 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">
                                <div class="rounded-2xl border border-zinc-200 bg-white p-2 shadow-xl dark:border-zinc-800 dark:bg-zinc-900">
                                    @foreach ($navCategories->where('department', $key) as $category)
                                        <a href="{{ route('category', $category) }}" wire:navigate.hover class="block rounded-xl px-3 py-2 font-medium hover:bg-brand-50 hover:text-brand-800 dark:hover:bg-brand-950 dark:hover:text-brand-300">{{ $category->name }}</a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <a href="{{ route('shop', ['sort' => 'newest']) }}" wire:navigate.hover class="hidden rounded-full px-3 py-2 transition xl:block hover:bg-zinc-100 hover:text-brand-700 dark:hover:bg-zinc-800 dark:hover:text-brand-400">New in</a>
                    <a href="{{ route('shop', ['sale' => 1]) }}" wire:navigate.hover class="rounded-full px-3 py-2 text-amber-600 transition hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-950/40">Sale</a>
                </nav>

                <div class="ms-auto flex items-center gap-1 sm:gap-2">
                    <livewire:search-box />

                    <button type="button" x-on:click="$flux.dark = ! $flux.dark" class="grid size-10 place-items-center rounded-full transition hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="Switch light or dark mode">
                        <flux:icon.moon variant="outline" class="size-5 dark:hidden" />
                        <flux:icon.sun variant="outline" class="hidden size-5 dark:block" />
                    </button>

                    @auth
                        <a href="{{ route('account.wishlist') }}" wire:navigate.hover class="hidden size-10 place-items-center rounded-full transition hover:bg-zinc-100 sm:grid dark:hover:bg-zinc-800" aria-label="Wishlist">
                            <flux:icon.heart variant="outline" class="size-5" />
                        </a>
                    @endauth

                    <a href="{{ auth()->check() ? (auth()->user()->is_admin ? route('admin.dashboard') : route('account.orders')) : route('login') }}" class="grid size-10 place-items-center rounded-full transition hover:bg-zinc-100 dark:hover:bg-zinc-800" aria-label="{{ auth()->check() ? 'My account' : 'Log in' }}">
                        <flux:icon.user variant="outline" class="size-5" />
                    </a>

                    <livewire:cart-drawer />
                </div>
            </div>

            {{-- Mobile menu. Moved to <body> because the header's blur effect would otherwise trap it inside the header bar. --}}
            <template x-teleport="body">
            <div x-cloak x-show="menu" x-effect="document.documentElement.classList.toggle('overflow-hidden', menu)" class="fixed inset-0 z-50 lg:hidden" x-on:keydown.escape.window="menu = false" role="dialog" aria-modal="true" aria-label="Menu">
                <div x-show="menu" x-transition.opacity class="absolute inset-0 bg-zinc-950/50 backdrop-blur-sm" x-on:click="menu = false"></div>
                <nav x-show="menu" x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="-translate-x-full" x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="-translate-x-full" class="absolute inset-y-0 start-0 flex w-80 max-w-[85vw] flex-col overflow-y-auto bg-white p-5 shadow-2xl dark:bg-zinc-900" aria-label="Mobile">
                    <div class="mb-6 flex items-center justify-between">
                        <x-store-logo />
                        <button type="button" class="grid size-10 place-items-center rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800" x-on:click="menu = false" aria-label="Close menu">
                            <flux:icon.x-mark />
                        </button>
                    </div>
                    <a href="{{ route('home') }}" wire:navigate.hover class="flex items-center gap-2 rounded-xl px-3 py-3 font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800"><flux:icon.home variant="mini" class="text-brand-600" /> Home</a>
                    <a href="{{ route('shop') }}" wire:navigate.hover class="flex items-center gap-2 rounded-xl px-3 py-3 font-semibold hover:bg-zinc-100 dark:hover:bg-zinc-800"><flux:icon.squares-2x2 variant="mini" class="text-brand-600" /> Shop all</a>
                    @foreach (\App\Models\Category::DEPARTMENTS as $key => $label)
                        <p class="mt-4 px-3 text-xs font-bold tracking-widest text-zinc-400 uppercase">{{ $label }}</p>
                        @foreach ($navCategories->where('department', $key) as $category)
                            <a href="{{ route('category', $category) }}" wire:navigate.hover class="rounded-xl px-3 py-2.5 font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ $category->name }}</a>
                        @endforeach
                    @endforeach
                    <div class="my-4 border-t"></div>
                    <a href="{{ route('shop', ['sort' => 'newest']) }}" wire:navigate.hover class="rounded-xl px-3 py-2.5 font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800">New in</a>
                    <a href="{{ route('shop', ['sale' => 1]) }}" wire:navigate.hover class="rounded-xl px-3 py-2.5 font-medium text-amber-600 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-zinc-800">Sale</a>
                    <a href="{{ route('orders.track') }}" wire:navigate.hover class="rounded-xl px-3 py-2.5 font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800">Track my order</a>
                    <a href="{{ route('contact') }}" wire:navigate.hover class="rounded-xl px-3 py-2.5 font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800">Contact us</a>
                    @auth
                        <a href="{{ route('account.wishlist') }}" wire:navigate.hover class="rounded-xl px-3 py-2.5 font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800">My wishlist</a>
                        <a href="{{ route('account.orders') }}" wire:navigate.hover class="rounded-xl px-3 py-2.5 font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800">My account</a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-xl px-3 py-2.5 font-medium hover:bg-zinc-100 dark:hover:bg-zinc-800" wire:navigate.hover>Log in / Sign up</a>
                    @endauth
                </nav>
            </div>
            </template>
        </header>

        <main id="main">
            {{ $slot }}
        </main>

        @include('partials.shop-footer', ['store' => $store, 'navCategories' => $navCategories])

        @if ($whatsappChat = \App\Models\Setting::whatsappUrl('Hello Kimdern Store, I have a question.'))
            <a href="{{ $whatsappChat }}" target="_blank" rel="noopener" class="group fixed end-4 bottom-24 z-30 flex items-center gap-2 rounded-full bg-[#25D366] p-3.5 text-white shadow-xl shadow-green-900/20 transition hover:-translate-y-1 hover:shadow-2xl lg:end-6 lg:bottom-6" aria-label="Chat with us on WhatsApp">
                <span class="absolute inset-0 -z-10 animate-ping rounded-full bg-[#25D366] opacity-30 group-hover:hidden"></span>
                <svg viewBox="0 0 24 24" fill="currentColor" class="size-6" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2c-1.6 0-3.1-.4-4.4-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.4.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.4.1-.7.3-.2.2-.9.9-.9 2.2s.9 2.5 1 2.7c.1.2 1.8 2.8 4.4 3.9 1.6.7 2.3.8 3.1.6.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.3-.2-.5-.3Z" /></svg>
                <span class="hidden pe-1 text-sm font-bold sm:group-hover:inline">Chat with us</span>
            </a>
        @endif

        @include('partials.cookie-notice')

        @persist('toast')
            <flux:toast.group position="bottom end">
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
