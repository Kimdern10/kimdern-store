<?php

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Livewire\Component;

/**
 * The sign up form. Uses the same CreateNewUser rules as Fortify, without reloading on mistakes.
 */
new class extends Component {
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function register(CreatesNewUsers $creator): void
    {
        $this->resetErrorBag();

        $user = $creator->create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ]);

        event(new Registered($user));

        Auth::login($user);
        session()->regenerate();

        // Full page load so the page gets the new security token after logging in.
        $this->redirect(route('dashboard'));
    }
}; ?>

<form wire:submit="register" class="flex flex-col gap-5">
    <flux:input wire:model="name" name="name" label="Full name" type="text" required autofocus autocomplete="name" icon="user" placeholder="e.g. Adaeze Okafor" />

    <div class="grid gap-5 sm:grid-cols-2">
        <flux:input wire:model="email" name="email" label="Email address" type="email" required autocomplete="email" icon="envelope" placeholder="you@example.com" />
        <flux:input wire:model="phone" name="phone" label="Phone (optional)" type="tel" autocomplete="tel" icon="phone" placeholder="0803 123 4567" />
    </div>

    <flux:input
        wire:model="password"
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

    <flux:input wire:model="password_confirmation" name="password_confirmation" label="Confirm password" type="password" required autocomplete="new-password" icon="lock-closed" placeholder="Type it again" viewable />

    <flux:button type="submit" variant="primary" class="h-12 w-full" data-test="register-user-button">
        <span wire:loading.remove wire:target="register">Create my account</span>
        <span wire:loading wire:target="register" class="flex items-center gap-2"><span class="size-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span> Creating your account…</span>
    </flux:button>

    <p class="text-center text-xs text-zinc-500">By signing up you agree to our <a href="{{ route('shipping') }}" class="underline" wire:navigate.hover>delivery & returns</a> and <a href="{{ route('privacy') }}" class="underline" wire:navigate.hover>privacy</a> policies. We'll email you a link to confirm your address.</p>
</form>
