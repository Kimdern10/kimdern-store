<x-layouts::shop>
    <div class="container-shop max-w-xl pt-14">
        <div class="text-center" data-reveal>
            <div class="mx-auto grid size-16 place-items-center rounded-2xl bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-300"><flux:icon.truck class="size-8" /></div>
            <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-brand-950 sm:text-4xl dark:text-white">Track your order</h1>
            <p class="mt-2 text-zinc-600 dark:text-zinc-400">Enter the order number from your email (it starts with KDS-) and the email you used at checkout.</p>
        </div>

        <form method="get" action="{{ route('orders.track') }}" class="mt-8 space-y-5 rounded-3xl border p-6 sm:p-8" data-reveal="100">
            <flux:input name="number" label="Order number" placeholder="KDS-ABC1234" :value="old('number', request('number'))" required class="uppercase" />
            <flux:input name="email" type="email" label="Email address" :value="old('email', request('email'))" required />
            @error('number')<flux:callout variant="danger" icon="exclamation-circle" :heading="$message" />@enderror
            <flux:button type="submit" variant="primary" class="w-full">Find my order</flux:button>
        </form>

        <p class="mt-6 text-center text-sm text-zinc-500">Have an account? <a href="{{ route('account.orders') }}" class="font-semibold text-brand-700 hover:underline dark:text-brand-400">See all your orders</a>.</p>
    </div>
</x-layouts::shop>
