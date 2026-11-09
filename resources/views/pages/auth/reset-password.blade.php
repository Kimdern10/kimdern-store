<x-layouts::auth :title="__('Reset password')">
    <div class="flex flex-col gap-7">
        <x-auth-header icon="lock-open" title="Choose a new password" description="Pick something you haven't used here before, then log in with it." />

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.update') }}" class="flex flex-col gap-5" x-data="{ sending: false }" x-on:submit="sending = true">
            @csrf
            <input type="hidden" name="token" value="{{ request()->route('token') }}">

            <flux:input
                name="email"
                value="{{ old('email', request('email')) }}"
                label="Email address"
                type="email"
                required
                autocomplete="email"
                icon="envelope"
            />

            <flux:input
                name="password"
                label="New password"
                type="password"
                required
                autofocus
                autocomplete="new-password"
                icon="lock-closed"
                placeholder="Create a new password"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <x-password-strength for="password" />

            <flux:input
                name="password_confirmation"
                label="Confirm new password"
                type="password"
                required
                autocomplete="new-password"
                icon="lock-closed"
                placeholder="Type it again"
                viewable
            />

            <flux:button type="submit" variant="primary" class="h-12 w-full" data-test="reset-password-button" x-bind:disabled="sending">
                <span x-show="! sending">Save new password</span>
                <span x-show="sending" x-cloak>Saving…</span>
            </flux:button>
        </form>

        <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-1.5 text-sm font-semibold text-brand-700 hover:underline dark:text-brand-400">
            <flux:icon.arrow-left variant="micro" /> Back to log in
        </a>
    </div>
</x-layouts::auth>
