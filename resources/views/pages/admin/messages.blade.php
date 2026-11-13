<?php

use App\Livewire\Concerns\AdminOnly;
use App\Models\ContactMessage;
use Flux\Flux;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::admin'), Title('Messages')] class extends Component {
    use AdminOnly, WithPagination;

    #[Url(except: false)]
    public bool $unreadOnly = false;

    public ?int $openId = null;

    public function updatedUnreadOnly(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function messages(): LengthAwarePaginator
    {
        return ContactMessage::query()
            ->when($this->unreadOnly, fn ($q) => $q->whereNull('read_at'))
            ->latest()
            ->paginate(20);
    }

    #[Computed]
    public function unreadCount(): int
    {
        return ContactMessage::query()->whereNull('read_at')->count();
    }

    public function open(int $id): void
    {
        $this->openId = $this->openId === $id ? null : $id;
        ContactMessage::query()->whereKey($id)->whereNull('read_at')->update(['read_at' => now()]);
    }

    public function markUnread(int $id): void
    {
        ContactMessage::query()->whereKey($id)->update(['read_at' => null]);
        $this->openId = null;
    }

    public function delete(int $id): void
    {
        ContactMessage::query()->whereKey($id)->delete();
        Flux::toast(text: 'Message deleted.');
    }
}; ?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Messages</flux:heading>
            <flux:text>Sent from the contact page. Each one is also emailed to you; reply from your email.</flux:text>
        </div>
        <flux:switch wire:model.live="unreadOnly" label="Unread only ({{ $this->unreadCount }})" />
    </div>

    <div class="space-y-3">
        @forelse ($this->messages as $message)
            <article wire:key="message-{{ $message->id }}" @class(['rounded-2xl border bg-white transition dark:bg-zinc-900', 'border-brand-300 dark:border-brand-800' => ! $message->read_at, 'border-zinc-200 dark:border-zinc-800' => $message->read_at])>
                <button type="button" wire:click="open({{ $message->id }})" class="flex w-full items-start gap-4 p-5 text-start">
                    <span @class(['mt-2 size-2.5 shrink-0 rounded-full', 'bg-brand-500' => ! $message->read_at, 'bg-transparent' => $message->read_at])></span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p @class(['truncate', 'font-bold' => ! $message->read_at, 'font-medium' => $message->read_at])>{{ $message->name }} <span class="font-normal text-zinc-500">· {{ $message->subjectLabel() }}</span></p>
                            <time class="text-xs text-zinc-500">{{ $message->created_at->diffForHumans() }}</time>
                        </div>
                        <p class="mt-1 line-clamp-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $message->message }}</p>
                    </div>
                </button>
                @if ($openId === $message->id)
                    <div class="border-t border-zinc-100 p-5 dark:border-zinc-800">
                        <p class="text-sm whitespace-pre-line">{{ $message->message }}</p>
                        <dl class="mt-4 grid gap-1 text-sm text-zinc-600 sm:grid-cols-3 dark:text-zinc-400">
                            <div><dt class="text-xs text-zinc-400">Email</dt><dd><a href="mailto:{{ $message->email }}?subject={{ rawurlencode('Re: '.$message->subjectLabel()) }}" class="text-brand-700 hover:underline dark:text-brand-400">{{ $message->email }}</a></dd></div>
                            @if ($message->phone)<div><dt class="text-xs text-zinc-400">Phone</dt><dd><a href="tel:{{ $message->phone }}" class="text-brand-700 hover:underline dark:text-brand-400">{{ $message->phone }}</a></dd></div>@endif
                            @if ($message->order_number)
                                @php($order = \App\Models\Order::query()->where('number', $message->order_number)->first())
                                <div><dt class="text-xs text-zinc-400">Order</dt><dd>@if ($order)<a href="{{ route('admin.orders.show', $order) }}" class="text-brand-700 hover:underline dark:text-brand-400" wire:navigate.hover>{{ $order->number }}</a>@else{{ $message->order_number }} (not found)@endif</dd></div>
                            @endif
                        </dl>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <flux:button size="sm" variant="primary" icon="envelope" :href="'mailto:'.$message->email.'?subject='.rawurlencode('Re: '.$message->subjectLabel())">Reply by email</flux:button>
                            @if ($message->phone)
                                <flux:button size="sm" icon="chat-bubble-left-right" :href="'https://wa.me/'.preg_replace(['/\D+/', '/^0/'], ['', '234'], $message->phone)" target="_blank">WhatsApp</flux:button>
                            @endif
                            <flux:button size="sm" variant="ghost" wire:click="markUnread({{ $message->id }})">Mark unread</flux:button>
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $message->id }})" wire:confirm="Delete this message?">Delete</flux:button>
                        </div>
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-2xl border border-dashed border-zinc-300 p-12 text-center text-zinc-500 dark:border-zinc-700">No messages {{ $unreadOnly ? 'waiting' : 'yet' }}.</div>
        @endforelse
    </div>

    {{ $this->messages->links() }}
</div>
