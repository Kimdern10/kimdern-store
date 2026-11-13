<?php

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;

/**
 * "Forgot your password?": emails a reset link and shows a "check your email" message in place.
 */
new class extends Component {
    public string $email = '';

    public bool $sent = false;

    public function sendLink(): void
    {
        $this->validate(['email' => ['required', 'email']]);

        $key = 'forgot-password:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 6)) {
            $this->addError('email', 'Too many tries. Please wait a minute and try again.');

            return;
        }

        RateLimiter::hit($key, 60);

        $status = Password::broker()->sendResetLink(['email' => strtolower(trim($this->email))]);

        if ($status !== Password::RESET_LINK_SENT && $status !== Password::RESET_THROTTLED) {
            $this->addError('email', __($status));

            return;
        }

        $this->sent = true;
    }
}; ?>

<div class="flex flex-col gap-7">
    @if ($sent)
        <x-auth-header icon="envelope-open" title="Check your email" description="We've sent a link to {{ $email }} to choose a new password. It works for 60 minutes." />

        <div class="space-y-3 rounded-2xl border border-zinc-200 p-5 text-sm text-zinc-600 dark:border-zinc-800 dark:text-zinc-400">
            <p class="font-semibold text-zinc-800 dark:text-zinc-200">Didn't get it?</p>
            <ul class="list-disc space-y-1 ps-5">
                <li>Check your spam or promotions folder.</li>
                <li>Make sure you typed the email you signed up with.</li>
                <li>Wait a minute, then send it again.</li>
            </ul>
        </div>

        <flux:button wire:click="sendLink" class="h-12 w-full">
            <span wire:loading.remove wire:target="sendLink">Send the link again</span>
            <span wire:loading wire:target="sendLink">Sending…</span>
        </flux:button>
    @else
        <x-auth-header icon="key" title="Forgot your password?" description="No worries. Enter the email you signed up with and we'll send you a link to choose a new one." />

        <form wire:submit="sendLink" class="flex flex-col gap-5">
            <flux:input wire:model="email" name="email" label="Email address" type="email" required autofocus autocomplete="email" icon="envelope" placeholder="you@example.com" />

            <flux:button variant="primary" type="submit" class="h-12 w-full" data-test="email-password-reset-link-button">
                <span wire:loading.remove wire:target="sendLink">Send reset link</span>
                <span wire:loading wire:target="sendLink">Sending…</span>
            </flux:button>
        </form>
    @endif
</div>
