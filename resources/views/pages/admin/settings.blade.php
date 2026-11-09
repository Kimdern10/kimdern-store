<?php

use App\Livewire\Concerns\AdminOnly;
use App\Models\Setting;
use App\Support\ImageOptimizer;
use App\Support\Media;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin'), Title('Site settings')] class extends Component {
    use AdminOnly, WithFileUploads;

    /** @var array<string, string|null> */
    public array $values = [];

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $logo = null;

    public function mount(): void
    {
        $this->values = collect(Setting::values())->except('logo')->map(fn ($v) => (string) $v)->all();
    }

    public function save(ImageOptimizer $optimizer): void
    {
        $this->validate([
            'values.store_name' => ['required', 'string', 'max:60'],
            'values.tagline' => ['nullable', 'string', 'max:120'],
            'values.email' => ['required', 'email'],
            'values.phone' => ['required', 'string', 'max:30'],
            'values.whatsapp' => ['nullable', 'regex:/^[0-9]{10,15}$/'],
            'values.address' => ['nullable', 'string', 'max:200'],
            'values.instagram_url' => ['nullable', 'url'],
            'values.facebook_url' => ['nullable', 'url'],
            'values.tiktok_url' => ['nullable', 'url'],
            'values.x_url' => ['nullable', 'url'],
            'values.announcement' => ['nullable', 'string', 'max:140'],
            'values.bank_name' => ['nullable', 'string', 'max:60'],
            'values.bank_account_name' => ['nullable', 'string', 'max:80'],
            'values.bank_account_number' => ['nullable', 'digits:10'],
            'values.low_stock_threshold' => ['required', 'integer', 'min:0', 'max:1000'],
            'values.flash_sale_title' => ['nullable', 'string', 'max:60'],
            'values.flash_sale_ends_at' => ['nullable', 'date'],
            'values.meta_description' => ['nullable', 'string', 'max:300'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:4096'],
        ], [
            'values.whatsapp.regex' => 'Write the WhatsApp number with country code and no + or spaces, e.g. 2348031234567.',
            'values.bank_account_number.digits' => 'A NUBAN account number has 10 digits.',
        ], [
            'values.store_name' => 'shop name', 'values.email' => 'email', 'values.phone' => 'phone',
            'values.instagram_url' => 'Instagram link', 'values.facebook_url' => 'Facebook link', 'values.tiktok_url' => 'TikTok link', 'values.x_url' => 'X link',
        ]);

        $values = collect($this->values)->map(fn ($v) => filled($v) ? trim((string) $v) : null)->all();

        if ($this->logo) {
            Media::delete(Setting::get('logo'));
            $values['logo'] = $optimizer->storeLogo($this->logo);
            $this->logo = null;
        }

        Setting::put($values);
        Flux::toast(variant: 'success', text: 'Settings saved.');
    }

    public function removeLogo(): void
    {
        Media::delete(Setting::get('logo'));
        Setting::put(['logo' => null]);
        Flux::toast(text: 'Logo removed. The text logo is used instead.');
    }
}; ?>

<form wire:submit="save" class="max-w-4xl space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Site settings</flux:heading>
            <flux:text>Your shop's name, logo, contacts and bank details. These appear on the website, emails and receipts.</flux:text>
        </div>
        <flux:button type="submit" variant="primary" icon="check">Save settings</flux:button>
    </div>

    <section class="grid gap-4 rounded-2xl border border-zinc-200 bg-white p-5 sm:grid-cols-2 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="sm:col-span-2">Brand</flux:heading>
        <flux:input wire:model="values.store_name" label="Shop name" />
        <flux:input wire:model="values.tagline" label="Tagline" />
        <div class="sm:col-span-2">
            <flux:label>Logo</flux:label>
            <div class="mt-2 flex flex-wrap items-center gap-4">
                <div class="grid h-16 min-w-32 place-items-center rounded-xl border border-dashed border-zinc-300 bg-zinc-50 px-4 dark:border-zinc-700 dark:bg-zinc-800">
                    @if ($logo && $logo->isPreviewable())
                        <img src="{{ $logo->temporaryUrl() }}" alt="New logo" class="max-h-12">
                    @elseif (\App\Models\Setting::logoUrl())
                        <img src="{{ \App\Models\Setting::logoUrl() }}" alt="Current logo" class="max-h-12">
                    @else
                        <x-store-logo />
                    @endif
                </div>
                <input type="file" wire:model="logo" accept="image/png,image/jpeg,image/webp" class="text-sm">
                @if (\App\Models\Setting::get('logo'))
                    <flux:button size="sm" variant="ghost" wire:click="removeLogo">Use text logo</flux:button>
                @endif
            </div>
            <flux:text class="mt-1">A PNG with a transparent background works best. It's resized automatically.</flux:text>
            @error('logo')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="sm:col-span-2"><flux:input wire:model="values.announcement" label="Announcement bar" description="The strip at the very top of the site. Leave empty to hide it." /></div>
    </section>

    <section class="grid gap-4 rounded-2xl border border-zinc-200 bg-white p-5 sm:grid-cols-2 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="sm:col-span-2">Contact</flux:heading>
        <flux:input wire:model="values.email" type="email" label="Email" description="New-order alerts go here too." />
        <flux:input wire:model="values.phone" label="Phone" />
        <flux:input wire:model="values.whatsapp" label="WhatsApp number" placeholder="2348031234567" />
        <flux:input wire:model="values.address" label="Address" />
    </section>

    <section class="grid gap-4 rounded-2xl border border-zinc-200 bg-white p-5 sm:grid-cols-2 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading class="sm:col-span-2">Social media</flux:heading>
        <flux:input wire:model="values.instagram_url" label="Instagram link" placeholder="https://instagram.com/yourshop" />
        <flux:input wire:model="values.facebook_url" label="Facebook link" />
        <flux:input wire:model="values.tiktok_url" label="TikTok link" />
        <flux:input wire:model="values.x_url" label="X (Twitter) link" />
    </section>

    <section class="grid gap-4 rounded-2xl border border-zinc-200 bg-white p-5 sm:grid-cols-3 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="sm:col-span-3">
            <flux:heading>Bank transfer details</flux:heading>
            <flux:text>Shown to customers who pay by transfer (always used while Paystack keys are not set).</flux:text>
        </div>
        <flux:input wire:model="values.bank_name" label="Bank" />
        <flux:input wire:model="values.bank_account_name" label="Account name" />
        <flux:input wire:model="values.bank_account_number" label="Account number" />
    </section>

    <section class="grid gap-4 rounded-2xl border border-zinc-200 bg-white p-5 sm:grid-cols-2 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="sm:col-span-2">
            <flux:heading>Flash sale</flux:heading>
            <flux:text>Shows a countdown on the home page with every product that has a “was” price. It hides itself when the time runs out. Leave the end time empty to switch it off.</flux:text>
        </div>
        <flux:input wire:model="values.flash_sale_title" label="Sale name" placeholder="e.g. Black Friday sale" />
        <flux:input wire:model="values.flash_sale_ends_at" type="datetime-local" label="Ends at (Lagos time)" />
        @if (\App\Models\Setting::flashSaleEndsAt())
            <flux:callout class="sm:col-span-2" variant="success" icon="bolt" text="The sale is live and ends {{ \App\Models\Setting::flashSaleEndsAt()->format('l j F, g:i a') }}." />
        @endif
    </section>

    <section class="grid gap-4 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <flux:heading>Shop & search engines</flux:heading>
        <flux:input wire:model="values.low_stock_threshold" type="number" min="0" label="Low stock warning at" description="Products with this many or fewer left show on the dashboard and say 'only X left'." class="max-w-xs" />
        <flux:textarea wire:model="values.meta_description" label="Home page description for Google" rows="3" />
    </section>

    <div class="flex justify-end">
        <flux:button type="submit" variant="primary" icon="check">Save settings</flux:button>
    </div>
</form>
