<?php

use App\Support\Seo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('My orders')] class extends Component {
    use WithPagination;

    public function mount(): void
    {
        Seo::set('My orders')->noindex = true;
    }

    #[Computed]
    public function orders(): LengthAwarePaginator
    {
        return Auth::user()->orders()->with('items')->latest()->paginate(10);
    }
}; ?>

<div>
    <h2 class="text-xl font-bold">My orders</h2>

    @if ($this->orders->isEmpty())
        <div class="mt-6 rounded-3xl border border-dashed p-10 text-center">
            <flux:icon.shopping-bag class="mx-auto size-10 text-zinc-300" />
            <p class="mt-3 font-semibold">You haven't ordered anything yet.</p>
            <p class="text-sm text-zinc-500">Orders you place while logged in will show here.</p>
            <flux:button variant="primary" class="mt-5" href="{{ route('shop') }}">Start shopping</flux:button>
        </div>
    @else
        <div class="mt-6 space-y-4">
            @foreach ($this->orders as $order)
                <a wire:key="order-{{ $order->id }}" href="{{ route('account.order', $order) }}" class="group block rounded-3xl border p-5 transition hover:border-brand-300 hover:shadow-lg">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="font-bold">{{ $order->number }}</p>
                            <p class="text-sm text-zinc-500">{{ $order->created_at->format('j M Y') }} · {{ $order->itemCount() }} {{ \Illuminate\Support\Str::plural('item', $order->itemCount()) }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <flux:badge :color="$order->status->colour()" size="sm">{{ $order->status->label() }}</flux:badge>
                            <span class="font-extrabold">{{ naira($order->total) }}</span>
                            <flux:icon.chevron-right variant="micro" class="text-zinc-400 transition group-hover:translate-x-1" />
                        </div>
                    </div>
                    <div class="mt-4 flex -space-x-3">
                        @foreach ($order->items->take(5) as $item)
                            <img src="{{ $item->imageUrl() }}" alt="" class="size-12 rounded-xl object-cover ring-2 ring-white dark:ring-zinc-950">
                        @endforeach
                    </div>
                </a>
            @endforeach
        </div>
        <div class="mt-6">{{ $this->orders->links() }}</div>
    @endif
</div>
