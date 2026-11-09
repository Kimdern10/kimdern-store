<x-layouts::auth :title="__('Email verification')">
    <div class="flex flex-col gap-7">
        <x-auth-header icon="envelope-open" title="Confirm your email" description="We've sent a confirmation link to {{ auth()->user()?->email ?? 'your email' }}. Tap it to finish setting up your account." />

        @if (session('status') == 'verification-link-sent')
            <div class="flex items-center gap-3 rounded-2xl bg-brand-50 px-4 py-3 text-sm font-semibold text-brand-800 dark:bg-brand-950/50 dark:text-brand-300">
                <flux:icon.check-circle class="size-5 shrink-0" /> A new link is on its way. Check your inbox.
            </div>
        @endif

        <div class="rounded-2xl border border-zinc-200 p-5 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
            <p>Can't find it? Look in spam or promotions, or send it again. You can keep shopping while you wait: your cart is saved.</p>
        </div>

        <div class="flex flex-col gap-3">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <flux:button type="submit" variant="primary" class="h-12 w-full">Send the link again</flux:button>
            </form>

            <flux:button :href="route('home')" class="h-12 w-full">Keep shopping</flux:button>

            <form method="POST" action="{{ route('logout') }}" class="text-center">
                @csrf
                <button type="submit" class="text-sm font-semibold text-zinc-500 hover:text-red-600" data-test="logout-button">Log out</button>
            </form>
        </div>
    </div>
</x-layouts::auth>
