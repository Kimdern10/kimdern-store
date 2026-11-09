<x-layouts::auth :title="__('Forgot password')">
    <div class="flex flex-col gap-7">
        @if (session('status'))
            <x-auth-header icon="envelope-open" title="Check your email" description="If an account uses that email, we've sent a link to reset your password. It works for 60 minutes." />

            <div class="space-y-3 rounded-2xl border border-zinc-200 p-5 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
                <p class="font-semibold text-zinc-800 dark:text-zinc-200">Didn't get it?</p>
                <ul class="list-disc space-y-1 ps-5">
                    <li>Check your spam or promotions folder.</li>
                    <li>Make sure you typed the email you signed up with.</li>
                    <li>Wait a minute, then try again below.</li>
                </ul>
            </div>
        @else
            <x-auth-header icon="key" title="Forgot your password?" description="No worries. Enter the email you signed up with and we'll send you a link to choose a new one." />
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-5" x-data="{ sending: false }" x-on:submit="sending = true">
            @csrf

            <flux:input
                name="email"
                label="Email address"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                icon="envelope"
                placeholder="you@example.com"
            />

            <flux:button variant="primary" type="submit" class="h-12 w-full" data-test="email-password-reset-link-button" x-bind:disabled="sending">
                <span x-show="! sending">{{ session('status') ? 'Send the link again' : 'Send reset link' }}</span>
                <span x-show="sending" x-cloak>Sending…</span>
            </flux:button>
        </form>

        <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-1.5 text-sm font-semibold text-brand-700 hover:underline dark:text-brand-400">
            <flux:icon.arrow-left variant="micro" /> Back to log in
        </a>
    </div>
</x-layouts::auth>
