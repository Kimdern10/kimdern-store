<?php

use App\Livewire\Concerns\AdminOnly;
use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin'), Title('Customers')] class extends Component {
    use AdminOnly, WithPagination;

    #[Url(except: '')]
    public string $search = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function customers(): LengthAwarePaginator
    {
        return User::query()->where('is_admin', false)
            ->withCount('orders')
            ->withSum(['orders as spent' => fn ($q) => $q->whereNotNull('paid_at')->where('status', '!=', 'cancelled')], 'total')
            ->when(trim($this->search) !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.trim($this->search).'%')->orWhere('email', 'like', '%'.trim($this->search).'%')->orWhere('phone', 'like', '%'.trim($this->search).'%')))
            ->latest()
            ->paginate(25);
    }

    #[Computed]
    public function subscriberCount(): int
    {
        return NewsletterSubscriber::query()->count();
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <flux:heading size="xl">Customers</flux:heading>
            <flux:text>People with an account. Guest orders are on the Orders page. {{ $this->subscriberCount }} newsletter {{ \Illuminate\Support\Str::plural('subscriber', $this->subscriberCount) }}.</flux:text>
        </div>
        <flux:input wire:model.live.debounce.400ms="search" icon="magnifying-glass" placeholder="Search name, email or phone" class="max-w-sm" />
    </div>

    <div class="overflow-x-auto rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead class="border-b border-zinc-100 bg-zinc-50 text-xs tracking-wide text-zinc-500 uppercase dark:border-zinc-800 dark:bg-zinc-950/40">
                <tr><th class="px-5 py-3 text-start">Customer</th><th class="px-5 py-3 text-start">Phone</th><th class="px-5 py-3 text-start">Orders</th><th class="px-5 py-3 text-start">Spent</th><th class="px-5 py-3 text-start">Joined</th></tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->customers as $customer)
                    <tr wire:key="customer-{{ $customer->id }}">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-3">
                                <flux:avatar size="sm" :name="$customer->name" :initials="$customer->initials()" />
                                <div><p class="font-semibold">{{ $customer->name }}</p><a href="mailto:{{ $customer->email }}" class="text-xs text-zinc-500 hover:underline">{{ $customer->email }}</a>@unless ($customer->email_verified_at)<flux:badge size="sm" color="zinc" class="ms-1">Unverified</flux:badge>@endunless</div>
                            </div>
                        </td>
                        <td class="px-5 py-3">{{ $customer->phone ?? '—' }}</td>
                        <td class="px-5 py-3">@if ($customer->orders_count)<a href="{{ route('admin.orders', ['search' => $customer->email]) }}" class="font-semibold text-brand-700 hover:underline dark:text-brand-400">{{ $customer->orders_count }}</a>@else 0 @endif</td>
                        <td class="px-5 py-3 font-semibold">{{ naira($customer->spent ?? 0) }}</td>
                        <td class="px-5 py-3 text-zinc-500">{{ $customer->created_at->format('j M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-zinc-500">No customer accounts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->customers->links() }}
</div>
