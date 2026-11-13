<x-layouts::auth :title="__('Log in')">
    <div class="flex flex-col gap-7">
        <x-auth-header title="Welcome back 👋" description="Log in to track your orders, see your wishlist and check out faster." />

        <x-auth-session-status class="rounded-2xl bg-brand-50 px-4 py-3 text-center dark:bg-brand-950/50" :status="session('status')" />

        <x-passkey-verify />

        <livewire:auth.login-form />

        @if (Route::has('register'))
            <div class="flex items-center gap-4 text-xs font-semibold tracking-widest text-zinc-400 uppercase">
                <span class="h-px flex-1 bg-zinc-200 dark:bg-zinc-800"></span> New to {{ \Illuminate\Support\Str::of(\App\Models\Setting::get('store_name'))->before(' ') }}? <span class="h-px flex-1 bg-zinc-200 dark:bg-zinc-800"></span>
            </div>

            <a href="{{ route('register') }}" class="flex h-12 items-center justify-center gap-2 rounded-xl border-2 border-brand-600 font-bold text-brand-700 transition hover:bg-brand-50 dark:text-brand-300 dark:hover:bg-brand-950/50" wire:navigate.hover>
                <flux:icon.user-plus variant="micro" /> Create a free account
            </a>
        @endif

        <p class="text-center text-sm text-zinc-500">
            Just shopping? <a href="{{ route('cart') }}" class="font-semibold text-brand-700 hover:underline dark:text-brand-400" wire:navigate.hover>Check out as a guest</a> or <a href="{{ route('orders.track') }}" class="font-semibold text-brand-700 hover:underline dark:text-brand-400" wire:navigate.hover>track an order</a>.
        </p>
    </div>
</x-layouts::auth>
