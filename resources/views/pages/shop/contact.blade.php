<?php

use App\Mail\NewContactMessage;
use App\Models\ContactMessage;
use App\Models\Setting;
use App\Support\Mailer;
use App\Support\Seo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Contact details plus a message form. Messages land in the admin inbox and the shop's email.
 */
new #[Layout('layouts::shop')] class extends Component {
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $subject = 'order';

    public string $orderNumber = '';

    public string $message = '';

    /** Left empty by people; bots tend to fill every field. */
    public string $website = '';

    public bool $sent = false;

    public function mount(): void
    {
        Seo::set('Contact us', 'Questions about a product, an order or a delivery? Call, WhatsApp or send Kimdern Store a message.');

        if ($user = Auth::user()) {
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone = (string) $user->phone;
        }
    }

    public function send(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'subject' => ['required', Rule::in(array_keys(ContactMessage::SUBJECTS))],
            'orderNumber' => ['nullable', 'string', 'max:30'],
            'message' => ['required', 'string', 'min:10', 'max:3000'],
        ], [], ['orderNumber' => 'order number']);

        if ($this->website !== '') {
            $this->sent = true;

            return;
        }

        $key = 'contact:'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('message', 'You have sent a few messages already. Please wait a few minutes, or reach us on WhatsApp.');

            return;
        }

        RateLimiter::hit($key, 600);

        $contactMessage = ContactMessage::query()->create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'] ?: null,
            'subject' => $data['subject'],
            'order_number' => $data['orderNumber'] ? strtoupper(trim($data['orderNumber'])) : null,
            'message' => $data['message'],
        ]);

        Mailer::send((string) Setting::get('email'), new NewContactMessage($contactMessage));

        $this->reset('message', 'orderNumber');
        $this->sent = true;
    }
}; ?>

@php($store = \App\Models\Setting::values())
<div>
    <section class="bg-gradient-to-br from-brand-50 via-white to-amber-50 dark:from-brand-950 dark:via-zinc-950 dark:to-zinc-900">
        <div class="container-shop max-w-5xl py-14 text-center">
            <p class="text-sm font-bold tracking-widest text-amber-600 uppercase dark:text-amber-400">We are here to help</p>
            <h1 class="mt-2 text-4xl font-extrabold tracking-tight text-brand-950 sm:text-5xl dark:text-white">Contact us</h1>
            <p class="mx-auto mt-3 max-w-2xl text-zinc-600 dark:text-zinc-400">Questions about a product, an order or a delivery? Reach us any way you like. We usually reply within a few hours.</p>
        </div>
    </section>

    <div class="container-shop mt-12 grid max-w-6xl gap-10 lg:grid-cols-[1fr_1.2fr]">
        <div class="grid gap-4 self-start sm:grid-cols-2 lg:grid-cols-1">
            @foreach ([
                ['phone', 'Call us', $store['phone'], 'tel:'.preg_replace('/\s+/', '', (string) $store['phone']), 'Mon – Sat, 8am – 7pm'],
                ['chat-bubble-left-right', 'WhatsApp', 'Chat with us', \App\Models\Setting::whatsappUrl('Hello Kimdern Store'), 'Fastest way to reach us'],
                ['envelope', 'Email', $store['email'], 'mailto:'.$store['email'], 'We reply within one working day'],
                ['map-pin', 'Visit', $store['address'], null, 'Pick-up by appointment'],
            ] as $i => [$icon, $title, $value, $link, $note])
                <div data-reveal="{{ $i * 80 }}" class="flex gap-4 rounded-3xl border p-5 transition hover:-translate-y-0.5 hover:shadow-lg">
                    <div class="grid size-12 shrink-0 place-items-center rounded-2xl bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-300"><flux:icon :name="$icon" /></div>
                    <div class="min-w-0">
                        <h2 class="font-bold">{{ $title }}</h2>
                        @if ($link)
                            <a href="{{ $link }}" @if (str_starts_with($link, 'http')) target="_blank" rel="noopener" @endif class="block break-words font-semibold text-brand-700 hover:underline dark:text-brand-400">{{ $value }}</a>
                        @else
                            <p class="font-semibold">{{ $value }}</p>
                        @endif
                        <p class="text-sm text-zinc-500">{{ $note }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="rounded-3xl border p-6 sm:p-8" data-reveal="120">
            @if ($sent)
                <div class="flex flex-col items-center gap-4 py-10 text-center">
                    <div class="grid size-16 place-items-center rounded-full bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-300"><flux:icon.check class="size-8" /></div>
                    <h2 class="text-2xl font-bold">Message sent!</h2>
                    <p class="max-w-sm text-zinc-600 dark:text-zinc-400">Thank you, {{ \Illuminate\Support\Str::of($name)->before(' ') }}. We'll reply to {{ $email }} soon.</p>
                    <flux:button wire:click="$set('sent', false)">Send another message</flux:button>
                </div>
            @else
                <h2 class="text-2xl font-bold">Send us a message</h2>
                <form wire:submit="send" class="mt-6 grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="name" label="Your name" required />
                    <flux:input wire:model="email" type="email" label="Email" required />
                    <flux:input wire:model="phone" type="tel" label="Phone (optional)" />
                    <flux:select wire:model.live="subject" label="What is it about?">
                        @foreach (\App\Models\ContactMessage::SUBJECTS as $value => $label)
                            <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @if (in_array($subject, ['order', 'returns'], true))
                        <div class="sm:col-span-2"><flux:input wire:model="orderNumber" label="Order number (optional)" placeholder="KDS-..." class="uppercase" /></div>
                    @endif
                    <div class="sm:col-span-2"><flux:textarea wire:model="message" label="Message" rows="5" required /></div>
                    <div class="hidden" aria-hidden="true"><input type="text" wire:model="website" tabindex="-1" autocomplete="off"></div>
                    <flux:button type="submit" variant="primary" class="sm:col-span-2" icon="paper-airplane">
                        <span wire:loading.remove wire:target="send">Send message</span>
                        <span wire:loading wire:target="send">Sending…</span>
                    </flux:button>
                </form>
            @endif
        </div>
    </div>
</div>
