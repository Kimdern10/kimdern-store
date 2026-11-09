<x-layouts::auth :title="__('Create an account')">
    <div class="flex flex-col gap-7">
        <x-auth-header title="Create your account" description="It takes less than a minute. Then track every order, save addresses and keep a wishlist." />

        <div class="flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm dark:border-amber-900 dark:bg-amber-950/30">
            <flux:icon.gift class="size-6 shrink-0 text-amber-600" />
            <p>New here? Use code <span class="rounded bg-amber-400 px-1.5 py-0.5 font-mono font-bold text-brand-950">WELCOME10</span> for 10% off your first order.</p>
        </div>

        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('register.store') }}" class="flex flex-col gap-5" x-data="{ sending: false }" x-on:submit="sending = true">
            @csrf

            <flux:input
                name="name"
                label="Full name"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                icon="user"
                placeholder="e.g. Adaeze Okafor"
            />

            <div class="grid gap-5 sm:grid-cols-2">
                <flux:input
                    name="email"
                    label="Email address"
                    :value="old('email')"
                    type="email"
                    required
                    autocomplete="email"
                    icon="envelope"
                    placeholder="you@example.com"
                />

                <flux:input
                    name="phone"
                    label="Phone (optional)"
                    :value="old('phone')"
                    type="tel"
                    autocomplete="tel"
                    icon="phone"
                    placeholder="0803 123 4567"
                />
            </div>

            <flux:input
                name="password"
                label="Password"
                type="password"
                required
                autocomplete="new-password"
                icon="lock-closed"
                placeholder="Create a password"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <x-password-strength for="password" />

            <flux:input
                name="password_confirmation"
                label="Confirm password"
                type="password"
                required
                autocomplete="new-password"
                icon="lock-closed"
                placeholder="Type it again"
                viewable
            />

            <flux:button type="submit" variant="primary" class="h-12 w-full" data-test="register-user-button" x-bind:disabled="sending">
                <span x-show="! sending">Create my account</span>
                <span x-show="sending" x-cloak class="flex items-center gap-2"><span class="size-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span> Creating your account…</span>
            </flux:button>

            <p class="text-center text-xs text-zinc-500">By signing up you agree to our <a href="{{ route('shipping') }}" class="underline">delivery & returns</a> and <a href="{{ route('privacy') }}" class="underline">privacy</a> policies. We'll email you a link to confirm your address.</p>
        </form>

        <p class="text-center text-sm text-zinc-600 dark:text-zinc-400">
            Already have an account? <a href="{{ route('login') }}" class="font-bold text-brand-700 hover:underline dark:text-brand-400">Log in</a>
        </p>
    </div>
</x-layouts::auth>
