<x-layouts::auth :title="__('Create an account')">
    <div class="flex flex-col gap-7">
        <x-auth-header title="Create your account" description="It takes less than a minute. Then track every order, save addresses and keep a wishlist." />

        <div class="flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm dark:border-amber-900 dark:bg-amber-950/30">
            <flux:icon.gift class="size-6 shrink-0 text-amber-600" />
            <p>New here? Use code <span class="rounded bg-amber-400 px-1.5 py-0.5 font-mono font-bold text-brand-950">WELCOME10</span> for 10% off your first order.</p>
        </div>

        <x-auth-session-status class="text-center" :status="session('status')" />

        <livewire:auth.register-form />

        <p class="text-center text-sm text-zinc-600 dark:text-zinc-400">
            Already have an account? <a href="{{ route('login') }}" class="font-bold text-brand-700 hover:underline dark:text-brand-400" wire:navigate.hover>Log in</a>
        </p>
    </div>
</x-layouts::auth>
