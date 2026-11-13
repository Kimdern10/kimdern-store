<x-layouts::auth :title="__('Reset password')">
    <div class="flex flex-col gap-7">
        <x-auth-header icon="lock-open" title="Choose a new password" description="Pick something you haven't used here before, then log in with it." />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <livewire:auth.reset-password-form :token="request()->route('token')" :email="request('email')" />

        <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-1.5 text-sm font-semibold text-brand-700 hover:underline dark:text-brand-400" wire:navigate.hover>
            <flux:icon.arrow-left variant="micro" /> Back to log in
        </a>
    </div>
</x-layouts::auth>
