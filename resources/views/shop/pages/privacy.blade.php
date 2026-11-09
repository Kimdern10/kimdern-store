@php($store = \App\Models\Setting::values())
<div class="prose-shop space-y-8 text-zinc-600 dark:text-zinc-400">
    <section>
        <h2 class="mb-3 text-2xl font-bold text-zinc-900 dark:text-white">What we collect</h2>
        <p>When you order, we keep your name, email, phone number and delivery address so we can deliver your items and send you updates. If you create an account we also keep your saved addresses and wishlist.</p>
        <p>Card payments are handled by Paystack. Your card details go straight to Paystack and never touch our website.</p>
    </section>
    <section>
        <h2 class="mb-3 text-2xl font-bold text-zinc-900 dark:text-white">Cookies</h2>
        <p>We only use the cookies the site needs to work: one to keep your cart and one to keep you logged in. We do not use advertising or tracking cookies. Your light/dark mode choice is saved in your browser.</p>
    </section>
    <section>
        <h2 class="mb-3 text-2xl font-bold text-zinc-900 dark:text-white">Newsletter</h2>
        <p>If you join our mailing list we'll email you new arrivals and offers. You can ask us to remove you at any time.</p>
    </section>
    <section>
        <h2 class="mb-3 text-2xl font-bold text-zinc-900 dark:text-white">Your rights</h2>
        <p>Under the Nigeria Data Protection Act you can ask to see, correct or delete your data. Email <a href="mailto:{{ $store['email'] }}" class="font-semibold text-brand-700 underline dark:text-brand-400">{{ $store['email'] }}</a> and we'll help within 30 days. You can delete your account yourself from your account settings.</p>
    </section>
</div>
