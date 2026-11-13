<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Fortify\Features;
use Livewire\Component;

/**
 * The log in form. Wrong details show at once without reloading the page.
 * Works like Fortify's own log in: five tries a minute, and two-step login when it is switched on.
 */
new class extends Component {
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($this->email).'|'.request()->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $this->addError('email', __('auth.throttle', ['seconds' => RateLimiter::availableIn($throttleKey), 'minutes' => ceil(RateLimiter::availableIn($throttleKey) / 60)]));

            return;
        }

        $user = User::query()->where('email', Str::lower(trim($this->email)))->first();

        if (! $user || ! Hash::check($this->password, $user->password)) {
            RateLimiter::hit($throttleKey);
            $this->addError('email', __('auth.failed'));
            $this->reset('password');

            return;
        }

        RateLimiter::clear($throttleKey);

        if (Features::enabled(Features::twoFactorAuthentication()) && $user->two_factor_secret && $user->two_factor_confirmed_at) {
            session()->put(['login.id' => $user->getKey(), 'login.remember' => $this->remember]);
            $this->redirectRoute('two-factor.login');

            return;
        }

        Auth::login($user, $this->remember);
        session()->regenerate();

        // A full page load after logging in, so the page gets the new security token.
        $this->redirectIntended(default: $user->is_admin ? route('admin.dashboard') : route('dashboard'));
    }
}; ?>

<form wire:submit="login" class="flex flex-col gap-5">
    <flux:input
        wire:model="email"
        name="email"
        label="Email address"
        type="email"
        required
        autofocus
        autocomplete="email"
        icon="envelope"
        placeholder="you@example.com"
    />

    <div class="relative">
        <flux:input
            wire:model="password"
            name="password"
            label="Password"
            type="password"
            required
            autocomplete="current-password"
            icon="lock-closed"
            placeholder="Your password"
            viewable
        />

        @if (Route::has('password.request'))
            <a href="{{ route('password.request') }}" class="absolute end-0 top-0 text-sm font-semibold text-brand-700 hover:underline dark:text-brand-400" wire:navigate.hover>Forgot password?</a>
        @endif
    </div>

    <flux:checkbox wire:model="remember" label="Keep me logged in on this device" />

    <flux:button variant="primary" type="submit" class="h-12 w-full" data-test="login-button">
        <span wire:loading.remove wire:target="login">Log in</span>
        <span wire:loading wire:target="login" class="flex items-center gap-2"><span class="size-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span> Logging in…</span>
    </flux:button>
</form>
