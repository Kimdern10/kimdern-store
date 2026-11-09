<?php

use App\Support\Seo;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::shop')] class extends Component {
    public function mount(): void
    {
        Seo::set('Privacy & cookies', 'How Kimdern Store looks after your personal data, and the few cookies the site uses.');
    }
}; ?>

<div>
    <section class="bg-gradient-to-br from-brand-50 via-white to-amber-50 dark:from-brand-950 dark:via-zinc-950 dark:to-zinc-900">
        <div class="container-shop max-w-4xl py-14 text-center">
            <p class="text-sm font-bold tracking-widest text-amber-600 uppercase dark:text-amber-400">Your data</p>
            <h1 class="mt-2 text-4xl font-extrabold tracking-tight text-brand-950 sm:text-5xl dark:text-white">Privacy & cookies</h1>
        </div>
    </section>
    <div class="container-shop max-w-4xl pt-12">
        @include('shop.pages.privacy')
    </div>
</div>
