<div
    x-data="{
        show: false,
        init() {
            try { this.show = localStorage.getItem('kds.cookies') !== 'ok' } catch (e) { this.show = true }
        },
        accept() {
            this.show = false
            try { localStorage.setItem('kds.cookies', 'ok') } catch (e) {}
        },
    }"
    x-cloak
    x-show="show"
    x-transition:enter="transition duration-500 ease-out"
    x-transition:enter-start="translate-y-8 opacity-0"
    x-transition:leave="transition duration-200 ease-in"
    x-transition:leave-end="translate-y-8 opacity-0"
    class="fixed inset-x-3 bottom-3 z-50 sm:inset-x-auto sm:start-6 sm:bottom-6 sm:max-w-md"
    role="dialog"
    aria-live="polite"
    aria-label="Cookie notice"
>
    <div class="flex flex-col gap-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-2xl sm:flex-row sm:items-center dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex gap-3">
            <span class="text-2xl" aria-hidden="true">🍪</span>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                We use cookies to keep your cart and login working. That's all, no ad trackers.
                <a href="{{ route('privacy') }}" class="font-semibold text-brand-700 underline underline-offset-2 dark:text-brand-400" wire:navigate.hover>Learn more</a>
            </p>
        </div>
        <flux:button size="sm" variant="primary" class="shrink-0" x-on:click="accept()">Okay</flux:button>
    </div>
</div>
