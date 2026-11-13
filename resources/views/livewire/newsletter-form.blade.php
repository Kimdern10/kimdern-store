<?php

use App\Models\NewsletterSubscriber;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component {
    #[Validate('required|email:rfc|max:255', as: 'email address')]
    public string $email = '';

    public bool $subscribed = false;

    public function subscribe(): void
    {
        $this->validate();

        NewsletterSubscriber::query()->firstOrCreate(['email' => strtolower(trim($this->email))]);

        $this->subscribed = true;
        $this->reset('email');
    }
}; ?>

<div>
    @if ($subscribed)
        <div class="flex items-center gap-3 rounded-2xl bg-white/10 p-5 text-white">
            <flux:icon.check-circle class="size-8 text-amber-400" />
            <p><strong>You're in!</strong> Use code <span class="rounded bg-amber-400 px-1.5 py-0.5 font-mono font-bold text-brand-950">WELCOME10</span> at checkout for 10% off.</p>
        </div>
    @else
        <form wire:submit="subscribe" class="flex w-full flex-col gap-3 sm:flex-row sm:items-center">
            <label for="newsletter-email" class="sr-only">Email address</label>
            <div class="relative w-full sm:min-w-0 sm:flex-1">
                <flux:icon.envelope variant="micro" class="pointer-events-none absolute start-5 top-1/2 -translate-y-1/2 text-white/50" />
                <input id="newsletter-email" type="email" wire:model="email" placeholder="Your email address" required autocomplete="email" inputmode="email"
                    class="block h-14 w-full rounded-full border border-white/25 bg-white/10 ps-11 pe-5 text-base text-white placeholder:text-white/60 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/40 focus:outline-none">
            </div>
            <button type="submit" class="flex h-14 w-full shrink-0 items-center justify-center gap-2 rounded-full bg-amber-500 px-8 font-bold text-brand-950 transition hover:-translate-y-0.5 hover:bg-amber-400 hover:shadow-lg hover:shadow-amber-500/30 disabled:opacity-70 sm:w-auto" wire:loading.attr="disabled" wire:target="subscribe">
                <span wire:loading.remove wire:target="subscribe">Subscribe</span>
                <span wire:loading wire:target="subscribe" class="size-5 animate-spin rounded-full border-2 border-brand-950 border-t-transparent"></span>
            </button>
        </form>
        @error('email')
            <p class="mt-2 text-sm text-amber-300">{{ $message }}</p>
        @enderror
    @endif
</div>
