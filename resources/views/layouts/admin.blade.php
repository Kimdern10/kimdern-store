@props(['title' => null])
@php
    $pendingReviews = \App\Models\Review::query()->where('is_approved', false)->count();
    $openOrders = \App\Models\Order::query()->whereIn('status', ['pending', 'paid', 'processing'])->count();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <meta name="robots" content="noindex, nofollow" />
        <title>{{ filled($title) ? $title.' · ' : '' }}Admin · {{ \App\Models\Setting::get('store_name') }}</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml" />
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="min-h-screen bg-zinc-50 font-sans antialiased dark:bg-zinc-950">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('admin.dashboard') }}" />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item icon="home" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')">Dashboard</flux:sidebar.item>
                <flux:sidebar.item icon="shopping-bag" :href="route('admin.orders')" :current="request()->routeIs('admin.orders*')" :badge="$openOrders ?: null">Orders</flux:sidebar.item>
                <flux:sidebar.group heading="Catalogue" class="grid">
                    <flux:sidebar.item icon="cube" :href="route('admin.products')" :current="request()->routeIs('admin.products*')">Products</flux:sidebar.item>
                    <flux:sidebar.item icon="squares-2x2" :href="route('admin.categories')" :current="request()->routeIs('admin.categories')">Categories</flux:sidebar.item>
                    <flux:sidebar.item icon="star" :href="route('admin.reviews')" :current="request()->routeIs('admin.reviews')" :badge="$pendingReviews ?: null">Reviews</flux:sidebar.item>
                </flux:sidebar.group>
                <flux:sidebar.group heading="Sales" class="grid">
                    <flux:sidebar.item icon="users" :href="route('admin.customers')" :current="request()->routeIs('admin.customers')">Customers</flux:sidebar.item>
                    <flux:sidebar.item icon="ticket" :href="route('admin.coupons')" :current="request()->routeIs('admin.coupons')">Coupons</flux:sidebar.item>
                    <flux:sidebar.item icon="truck" :href="route('admin.zones')" :current="request()->routeIs('admin.zones')">Delivery zones</flux:sidebar.item>
                </flux:sidebar.group>
                <flux:sidebar.group heading="Website" class="grid">
                    <flux:sidebar.item icon="photo" :href="route('admin.banners')" :current="request()->routeIs('admin.banners')">Home banners</flux:sidebar.item>
                    <flux:sidebar.item icon="cog-6-tooth" :href="route('admin.settings')" :current="request()->routeIs('admin.settings')">Site settings</flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="arrow-top-right-on-square" :href="route('home')" target="_blank">View shop</flux:sidebar.item>
                <flux:sidebar.item icon="moon" x-on:click="$flux.dark = ! $flux.dark" as="button">Light / dark</flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <flux:header class="border-b border-zinc-200 bg-white lg:hidden dark:border-zinc-800 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
            <flux:spacer />
            <x-app-logo href="{{ route('admin.dashboard') }}" />
            <flux:spacer />
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button type="submit" variant="ghost" size="sm" icon="arrow-right-start-on-rectangle" aria-label="Log out" />
            </form>
        </flux:header>

        <flux:main class="!p-4 sm:!p-6 lg:!p-8">
            {{ $slot }}
        </flux:main>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
