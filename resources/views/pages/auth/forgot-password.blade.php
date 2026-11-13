<x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-7">
        <livewire:auth.forgot-password-form />

        <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-1.5 text-sm font-semibold text-brand-700 hover:underline dark:text-brand-400" wire:navigate.hover>
            <flux:icon.arrow-left variant="micro" /> Back to log in
        </a>
    </div>
</x-layouts::auth>
