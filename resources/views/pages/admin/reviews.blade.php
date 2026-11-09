<?php

use App\Livewire\Concerns\AdminOnly;
use App\Models\Review;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin'), Title('Reviews')] class extends Component {
    use AdminOnly, WithPagination;

    #[Url]
    public string $show = 'pending';

    public function updatedShow(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function reviews(): LengthAwarePaginator
    {
        return Review::query()->with('product:id,name,slug')
            ->where('is_approved', $this->show === 'approved')
            ->latest()
            ->paginate(20);
    }

    #[Computed]
    public function pendingCount(): int
    {
        return Review::query()->where('is_approved', false)->count();
    }

    public function approve(int $id): void
    {
        Review::query()->findOrFail($id)->update(['is_approved' => true]);
        Flux::toast(variant: 'success', text: 'Review is now on the product page.');
    }

    public function hide(int $id): void
    {
        Review::query()->findOrFail($id)->update(['is_approved' => false]);
        Flux::toast(text: 'Review hidden.');
    }

    public function delete(int $id): void
    {
        Review::query()->findOrFail($id)->delete();
        Flux::toast(text: 'Review deleted.');
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl">Reviews</flux:heading>
        <flux:text>New reviews wait here until you approve them.</flux:text>
    </div>

    <div class="flex gap-2">
        <button type="button" wire:click="$set('show', 'pending')" @class(['rounded-full px-4 py-2 text-sm font-semibold', 'bg-brand-700 text-white' => $show === 'pending', 'bg-white ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800' => $show !== 'pending'])>Waiting <span class="opacity-70">{{ $this->pendingCount }}</span></button>
        <button type="button" wire:click="$set('show', 'approved')" @class(['rounded-full px-4 py-2 text-sm font-semibold', 'bg-brand-700 text-white' => $show === 'approved', 'bg-white ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-800' => $show !== 'approved'])>Published</button>
    </div>

    <div class="space-y-3">
        @forelse ($this->reviews as $review)
            <article wire:key="review-{{ $review->id }}" class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <x-stars :rating="$review->rating" />
                        <p class="mt-2 font-semibold">{{ $review->title ?: 'No title' }}</p>
                        <p class="text-xs text-zinc-500">{{ $review->name }} · {{ $review->created_at->format('j M Y') }} · on <a href="{{ route('product', $review->product) }}" target="_blank" class="text-brand-700 hover:underline dark:text-brand-400">{{ $review->product->name }}</a> @if ($review->is_verified_buyer)<flux:badge size="sm" color="emerald">Verified buyer</flux:badge>@endif</p>
                    </div>
                    <div class="flex gap-2">
                        @if ($review->is_approved)
                            <flux:button size="sm" wire:click="hide({{ $review->id }})">Hide</flux:button>
                        @else
                            <flux:button size="sm" variant="primary" icon="check" wire:click="approve({{ $review->id }})">Approve</flux:button>
                        @endif
                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $review->id }})" wire:confirm="Delete this review?" aria-label="Delete" />
                    </div>
                </div>
                <p class="mt-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $review->body }}</p>
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-zinc-300 p-12 text-center text-zinc-500 dark:border-zinc-700">{{ $show === 'pending' ? 'No reviews waiting. 🎉' : 'No published reviews yet.' }}</div>
        @endforelse
    </div>

    {{ $this->reviews->links() }}
</div>
