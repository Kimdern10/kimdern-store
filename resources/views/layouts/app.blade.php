{{-- The customer's account area: the shop layout with an account menu beside the page. --}}
<x-layouts::shop :title="$title ?? null">
    <div class="container-shop pt-10">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-bold tracking-widest text-amber-600 uppercase dark:text-amber-400">My account</p>
                <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-brand-950 dark:text-white">Hello, {{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }} 👋</h1>
            </div>
            @if (auth()->user()->is_admin)
                <flux:button href="{{ route('admin.dashboard') }}" icon="cog-6-tooth" size="sm" wire:navigate.hover>Shop admin</flux:button>
            @endif
        </div>

        <div class="mt-8 grid gap-8 lg:grid-cols-[15rem_1fr]">
            <nav class="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4 lg:mx-0 lg:flex-col lg:gap-1 lg:px-0" aria-label="Account">
                @foreach ([
                    ['account.orders', 'shopping-bag', 'My orders', ['account.orders', 'account.order']],
                    ['account.wishlist', 'heart', 'Wishlist', ['account.wishlist']],
                    ['account.addresses', 'map-pin', 'Addresses', ['account.addresses']],
                    ['profile.edit', 'user-circle', 'Profile', ['profile.edit']],
                    ['security.edit', 'lock-closed', 'Password & security', ['security.edit']],
                    ['appearance.edit', 'swatch', 'Appearance', ['appearance.edit']],
                ] as [$route, $icon, $label, $active])
                    <a href="{{ route($route) }}" wire:navigate.hover @class(['flex shrink-0 items-center gap-3 rounded-full px-4 py-2.5 text-sm font-semibold transition lg:rounded-2xl', 'bg-brand-700 text-white shadow-lg shadow-brand-700/20' => request()->routeIs(...$active), 'hover:bg-zinc-100 dark:hover:bg-zinc-800' => ! request()->routeIs(...$active)])>
                        <flux:icon :name="$icon" variant="outline" class="size-5" /> {{ $label }}
                    </a>
                @endforeach
                <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-full px-4 py-2.5 text-sm font-semibold text-zinc-500 transition hover:bg-red-50 hover:text-red-600 lg:rounded-2xl dark:hover:bg-red-950/30">
                        <flux:icon.arrow-right-start-on-rectangle variant="outline" class="size-5" /> Log out
                    </button>
                </form>
            </nav>

            <div class="min-w-0">
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts::shop>
