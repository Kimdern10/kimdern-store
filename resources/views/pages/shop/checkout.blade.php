<?php

use App\Actions\PlaceOrder;
use App\Models\DeliveryZone;
use App\Models\Setting;
use App\Services\Paystack;
use App\Support\Cart;
use App\Support\Seo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Checkout for guests and customers: contact and delivery details, discount code, then pay.
 */
new #[Layout('layouts::shop')] class extends Component {
    public string $email = '';

    public string $name = '';

    public string $phone = '';

    public string $address = '';

    public string $city = '';

    public string $state = '';

    public string $notes = '';

    public ?int $addressId = null;

    public bool $saveAddress = true;

    public string $couponCode = '';

    public function mount(): void
    {
        Seo::set('Checkout')->noindex = true;

        if ($this->cart->isEmpty()) {
            $this->redirectRoute('cart');

            return;
        }

        if ($user = Auth::user()) {
            $this->email = $user->email;
            $this->name = $user->name;
            $this->phone = (string) $user->phone;

            if ($default = $user->addresses()->orderByDesc('is_default')->latest()->first()) {
                $this->useAddress($default->id);
            }
        }
    }

    #[Computed]
    public function cart(): Cart
    {
        return app(Cart::class);
    }

    /**
     * @return array{subtotal: int, discount: int, delivery_fee: int|null, total: int, zone: DeliveryZone|null}
     */
    #[Computed]
    public function totals(): array
    {
        return $this->cart->totals($this->state);
    }

    #[Computed]
    public function paysOnline(): bool
    {
        return app(Paystack::class)->isConfigured();
    }

    public function useAddress(int $id): void
    {
        $address = Auth::user()?->addresses()->find($id);

        if (! $address) {
            return;
        }

        $this->addressId = $address->id;
        $this->fill($address->only('name', 'phone', 'address', 'city', 'state'));
        $this->saveAddress = false;
        unset($this->totals);
    }

    public function updatedState(): void
    {
        unset($this->totals);
    }

    public function applyCoupon(): void
    {
        $this->resetErrorBag('couponCode');

        if ($problem = $this->cart->applyCoupon($this->couponCode)) {
            $this->addError('couponCode', $problem);

            return;
        }

        $this->couponCode = '';
        unset($this->totals);
    }

    public function removeCoupon(): void
    {
        $this->cart->removeCoupon();
        unset($this->totals);
    }

    public function placeOrder(PlaceOrder $placeOrder, Paystack $paystack): void
    {
        $details = $this->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9 ]{10,16}$/'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', Rule::in(DeliveryZone::STATES)],
            'notes' => ['nullable', 'string', 'max:500'],
        ], ['phone.regex' => 'Enter a valid phone number, e.g. 0803 123 4567.']);

        $method = $paystack->isConfigured() ? 'paystack' : 'bank_transfer';
        $user = Auth::user();

        $order = $placeOrder->handle($details, $method, $user);

        if ($user && $this->saveAddress && ! $this->addressId) {
            $user->addresses()->create($details + ['label' => 'Home', 'is_default' => ! $user->addresses()->exists()]);
        }

        if ($user && blank($user->phone)) {
            $user->forceFill(['phone' => $details['phone']])->save();
        }

        if ($method === 'paystack') {
            try {
                $this->redirect($paystack->initialize($order, route('checkout.callback')));

                return;
            } catch (\Throwable $e) {
                report($e);
                Log::warning('Paystack initialise failed; showing the order page instead.', ['order' => $order->number]);
            }
        }

        $this->redirect($order->trackingUrl());
    }
}; ?>

<div class="container-shop pt-10">
    <div class="flex items-center justify-between gap-4">
        <h1 class="text-3xl font-extrabold tracking-tight text-brand-950 sm:text-4xl dark:text-white">Checkout</h1>
        <p class="flex items-center gap-1.5 text-sm text-zinc-500"><flux:icon.lock-closed variant="micro" class="text-brand-600" /> Secure checkout</p>
    </div>

    @guest
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl bg-brand-50 px-5 py-4 text-sm dark:bg-brand-950/60">
            <p>Checking out as a guest. Have an account? Log in to save your address and track orders easily.</p>
            <a href="{{ route('login') }}" wire:navigate class="font-bold text-brand-700 hover:underline dark:text-brand-400">Log in →</a>
        </div>
    @endguest

    <form wire:submit="placeOrder" class="mt-8 grid gap-10 lg:grid-cols-[1fr_26rem]">
        <div class="space-y-8">
            @error('cart')
                <flux:callout variant="danger" icon="exclamation-triangle" :heading="$message" />
            @enderror

            <section class="rounded-3xl border p-6" aria-labelledby="contact-title">
                <h2 id="contact-title" class="flex items-center gap-3 text-lg font-bold"><span class="grid size-8 place-items-center rounded-full bg-brand-700 text-sm text-white">1</span> Contact details</h2>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2"><flux:input wire:model="email" type="email" label="Email" autocomplete="email" required description="We'll send your receipt and updates here." /></div>
                    <flux:input wire:model="name" label="Full name" autocomplete="name" required />
                    <flux:input wire:model="phone" type="tel" label="Phone number" autocomplete="tel" placeholder="0803 123 4567" required />
                </div>
            </section>

            <section class="rounded-3xl border p-6" aria-labelledby="delivery-title">
                <h2 id="delivery-title" class="flex items-center gap-3 text-lg font-bold"><span class="grid size-8 place-items-center rounded-full bg-brand-700 text-sm text-white">2</span> Delivery address</h2>

                @auth
                    @php($saved = auth()->user()->addresses()->latest()->get())
                    @if ($saved->isNotEmpty())
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            @foreach ($saved as $item)
                                <button type="button" wire:click="useAddress({{ $item->id }})" @class(['rounded-2xl border-2 p-4 text-start text-sm transition', 'border-brand-600 bg-brand-50 dark:bg-brand-950/50' => $addressId === $item->id, 'hover:border-brand-300' => $addressId !== $item->id])>
                                    <p class="font-bold">{{ $item->label }}</p>
                                    <p class="text-zinc-600 dark:text-zinc-400">{{ $item->name }} · {{ $item->phone }}</p>
                                    <p class="text-zinc-600 dark:text-zinc-400">{{ $item->oneLine() }}</p>
                                </button>
                            @endforeach
                        </div>
                        <p class="mt-4 text-xs text-zinc-500">Or type a different address below.</p>
                    @endif
                @endauth

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2"><flux:input wire:model="address" wire:change="$set('addressId', null)" label="Street address" autocomplete="street-address" placeholder="House number and street" required /></div>
                    <flux:input wire:model="city" wire:change="$set('addressId', null)" label="City / area" autocomplete="address-level2" placeholder="e.g. Ikeja" required />
                    <flux:select wire:model.live="state" label="State" required>
                        <flux:select.option value="">Choose your state</flux:select.option>
                        @foreach (\App\Models\DeliveryZone::STATES as $option)
                            <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <div class="sm:col-span-2"><flux:textarea wire:model="notes" label="Delivery note (optional)" rows="2" placeholder="Landmark, best time to call..." /></div>
                    @auth
                        @if (! $addressId)
                            <div class="sm:col-span-2"><flux:checkbox wire:model="saveAddress" label="Save this address for next time" /></div>
                        @endif
                    @endauth
                </div>

                @if ($state !== '')
                    <div class="mt-5 rounded-2xl px-4 py-3 text-sm {{ $this->totals['zone'] ? 'bg-brand-50 dark:bg-brand-950/50' : 'bg-red-50 text-red-700 dark:bg-red-950/40 dark:text-red-300' }}">
                        @if ($zone = $this->totals['zone'])
                            <flux:icon.truck variant="micro" class="inline" /> Delivery to <strong>{{ $state }}</strong>: {{ $this->totals['delivery_fee'] > 0 ? naira($this->totals['delivery_fee']) : 'FREE' }} · {{ $zone->eta }}
                        @else
                            Sorry, we don't deliver to {{ $state }} yet. Please contact us and we'll sort it out.
                        @endif
                    </div>
                @endif
                @error('state')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </section>

            <section class="rounded-3xl border p-6" aria-labelledby="payment-title">
                <h2 id="payment-title" class="flex items-center gap-3 text-lg font-bold"><span class="grid size-8 place-items-center rounded-full bg-brand-700 text-sm text-white">3</span> Payment</h2>
                @if ($this->paysOnline)
                    <div class="mt-5 flex items-start gap-4 rounded-2xl border-2 border-brand-600 bg-brand-50/60 p-4 dark:bg-brand-950/40">
                        <flux:icon.credit-card class="mt-0.5 size-6 shrink-0 text-brand-700" />
                        <div>
                            <p class="font-bold">Pay with card, bank transfer or USSD</p>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">You'll be taken to Paystack's secure page to pay. We never see your card details.</p>
                        </div>
                    </div>
                @else
                    <div class="mt-5 flex items-start gap-4 rounded-2xl border-2 border-brand-600 bg-brand-50/60 p-4 dark:bg-brand-950/40">
                        <flux:icon.building-library class="mt-0.5 size-6 shrink-0 text-brand-700" />
                        <div>
                            <p class="font-bold">Bank transfer</p>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">After you place your order we'll show you our account details ({{ \App\Models\Setting::get('bank_name') }}). We ship as soon as your payment arrives.</p>
                        </div>
                    </div>
                @endif
            </section>
        </div>

        {{-- Summary --}}
        <aside class="h-fit space-y-5 rounded-3xl border bg-zinc-50/70 p-6 lg:sticky lg:top-28 dark:bg-zinc-900/50">
            <h2 class="text-lg font-bold">Your order</h2>
            <ul class="max-h-72 space-y-4 overflow-y-auto pe-1">
                @foreach ($this->cart->lines() as $line)
                    <li wire:key="summary-{{ $line->key }}" class="flex items-center gap-3">
                        <div class="relative shrink-0">
                            <img src="{{ $line->product->imageUrl() }}" alt="" class="size-16 rounded-xl object-cover">
                            <span class="absolute -end-2 -top-2 grid size-6 place-items-center rounded-full bg-zinc-800 text-xs font-bold text-white">{{ $line->quantity }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold">{{ $line->product->name }}</p>
                            @if ($line->variantLabel())<p class="text-xs text-zinc-500">{{ $line->variantLabel() }}</p>@endif
                        </div>
                        <p class="text-sm font-bold">{{ naira($line->total()) }}</p>
                    </li>
                @endforeach
            </ul>

            @if ($coupon = $this->cart->coupon())
                <div class="flex items-center justify-between rounded-2xl bg-brand-50 px-4 py-3 text-sm dark:bg-brand-950">
                    <span><strong>{{ $coupon->code }}</strong> · {{ $coupon->summary() }}</span>
                    <button type="button" wire:click="removeCoupon" class="text-xs font-semibold text-zinc-500 hover:text-red-600">Remove</button>
                </div>
            @else
                <div class="flex gap-2">
                    <flux:input wire:model="couponCode" wire:keydown.enter.prevent="applyCoupon" placeholder="Discount code" aria-label="Discount code" class="uppercase" />
                    <flux:button type="button" wire:click="applyCoupon">Apply</flux:button>
                </div>
                @error('couponCode')<p class="-mt-3 text-sm text-red-600">{{ $message }}</p>@enderror
            @endif

            <dl class="space-y-2 border-t pt-4 text-sm">
                <div class="flex justify-between"><dt>Subtotal</dt><dd class="font-semibold">{{ naira($this->totals['subtotal']) }}</dd></div>
                @if ($this->totals['discount'] > 0)
                    <div class="flex justify-between text-brand-700 dark:text-brand-400"><dt>Discount</dt><dd class="font-semibold">−{{ naira($this->totals['discount']) }}</dd></div>
                @endif
                <div class="flex justify-between"><dt>Delivery</dt><dd class="font-semibold">{{ $this->totals['delivery_fee'] === null ? 'Choose a state' : ($this->totals['delivery_fee'] > 0 ? naira($this->totals['delivery_fee']) : 'FREE') }}</dd></div>
            </dl>
            <div class="flex justify-between border-t pt-4 text-xl font-extrabold"><span>Total</span><span>{{ naira($this->totals['total']) }}</span></div>

            <button type="submit" class="flex h-14 w-full items-center justify-center gap-2 rounded-full bg-brand-700 font-bold text-white shadow-lg shadow-brand-700/25 transition hover:-translate-y-0.5 hover:bg-brand-800 disabled:opacity-70" wire:loading.attr="disabled" wire:target="placeOrder">
                <span wire:loading.remove wire:target="placeOrder" class="flex items-center gap-2"><flux:icon.lock-closed variant="micro" /> {{ $this->paysOnline ? 'Pay '.naira($this->totals['total']) : 'Place order' }}</span>
                <span wire:loading wire:target="placeOrder" class="flex items-center gap-2"><span class="size-5 animate-spin rounded-full border-2 border-white border-t-transparent"></span> Please wait...</span>
            </button>
            <p class="text-center text-xs text-zinc-500">By placing your order you agree to our <a href="{{ route('shipping') }}" wire:navigate class="underline">delivery & returns policy</a>.</p>
        </aside>
    </form>
</div>
