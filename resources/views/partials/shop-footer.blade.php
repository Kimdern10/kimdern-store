<footer class="mt-24 bg-brand-950 text-brand-100/80">
    {{-- Newsletter --}}
    <div class="border-b border-white/10">
        <div class="container-shop grid gap-8 py-14 lg:grid-cols-2 lg:items-center">
            <div data-reveal>
                <p class="text-sm font-bold tracking-widest text-amber-400 uppercase">Join the Kimdern club</p>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Get 10% off your first order</h2>
                <p class="mt-3 max-w-md">New arrivals, beauty tips and members-only deals. No spam, unsubscribe any time.</p>
            </div>
            <div data-reveal="120">
                <livewire:newsletter-form />
            </div>
        </div>
    </div>

    <div class="container-shop grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-5">
        <div class="lg:col-span-2">
            <x-store-logo light />
            <p class="mt-4 max-w-sm text-sm leading-relaxed">{{ $store['tagline'] }}. 100% genuine products, secure payment and delivery to all 36 states and Abuja.</p>
            <div class="mt-6 flex gap-2">
                @foreach (['instagram_url' => 'Instagram', 'facebook_url' => 'Facebook', 'tiktok_url' => 'TikTok', 'x_url' => 'X'] as $key => $label)
                    @if (filled($store[$key]))
                        <a href="{{ $store[$key] }}" target="_blank" rel="noopener" class="grid size-10 place-items-center rounded-full bg-white/10 text-xs font-bold text-white transition hover:-translate-y-0.5 hover:bg-amber-500 hover:text-brand-950" aria-label="{{ $label }}">
                            <x-social-icon :name="$label" />
                        </a>
                    @endif
                @endforeach
                @if ($whatsapp = \App\Models\Setting::whatsappUrl())
                    <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="grid size-10 place-items-center rounded-full bg-white/10 text-white transition hover:-translate-y-0.5 hover:bg-amber-500 hover:text-brand-950" aria-label="WhatsApp">
                        <x-social-icon name="WhatsApp" />
                    </a>
                @endif
            </div>
        </div>

        <div>
            <h3 class="text-sm font-bold tracking-widest text-white uppercase">Shop</h3>
            <ul class="mt-4 space-y-2.5 text-sm">
                @foreach ($navCategories as $category)
                    <li><a href="{{ route('category', $category) }}" wire:navigate.hover class="transition hover:text-amber-400">{{ $category->name }}</a></li>
                @endforeach
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-bold tracking-widest text-white uppercase">Help</h3>
            <ul class="mt-4 space-y-2.5 text-sm">
                <li><a href="{{ route('orders.track') }}" wire:navigate.hover class="transition hover:text-amber-400">Track my order</a></li>
                <li><a href="{{ route('shipping') }}" wire:navigate.hover class="transition hover:text-amber-400">Delivery & returns</a></li>
                <li><a href="{{ route('contact') }}" wire:navigate.hover class="transition hover:text-amber-400">Contact us</a></li>
                <li><a href="{{ route('privacy') }}" wire:navigate.hover class="transition hover:text-amber-400">Privacy & cookies</a></li>
                @auth
                    <li><a href="{{ route('account.orders') }}" wire:navigate.hover class="transition hover:text-amber-400">My orders</a></li>
                @else
                    <li><a href="{{ route('login') }}" wire:navigate.hover class="transition hover:text-amber-400">Log in / Sign up</a></li>
                @endauth
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-bold tracking-widest text-white uppercase">Contact</h3>
            <ul class="mt-4 space-y-3 text-sm">
                <li class="flex gap-2"><flux:icon.map-pin variant="micro" class="mt-0.5 shrink-0 text-amber-400" /> {{ $store['address'] }}</li>
                <li class="flex gap-2"><flux:icon.phone variant="micro" class="mt-0.5 shrink-0 text-amber-400" /> <a href="tel:{{ preg_replace('/\s+/', '', (string) $store['phone']) }}" class="hover:text-amber-400">{{ $store['phone'] }}</a></li>
                <li class="flex gap-2"><flux:icon.envelope variant="micro" class="mt-0.5 shrink-0 text-amber-400" /> <a href="mailto:{{ $store['email'] }}" class="break-all hover:text-amber-400">{{ $store['email'] }}</a></li>
            </ul>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="container-shop flex flex-col items-center justify-between gap-4 py-6 text-xs sm:flex-row">
            <p>© {{ date('Y') }} {{ $store['store_name'] }}. All rights reserved.</p>
            <div class="flex flex-wrap items-center justify-center gap-2" aria-label="Ways to pay">
                @foreach (['Visa', 'Mastercard', 'Verve', 'Bank transfer', 'USSD'] as $method)
                    <span class="rounded-md border border-white/15 bg-white/5 px-2 py-1 font-semibold text-white/80">{{ $method }}</span>
                @endforeach
            </div>
        </div>
    </div>
</footer>
