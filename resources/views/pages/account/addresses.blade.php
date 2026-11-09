<?php

use App\Models\Address;
use App\Models\DeliveryZone;
use App\Support\Seo;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('My addresses')] class extends Component {
    public ?int $editingId = null;

    public string $label = 'Home';

    public string $name = '';

    public string $phone = '';

    public string $address = '';

    public string $city = '';

    public string $state = '';

    public bool $isDefault = false;

    public function mount(): void
    {
        Seo::set('My addresses')->noindex = true;
    }

    /** @return Collection<int, Address> */
    #[Computed]
    public function addresses(): Collection
    {
        return Auth::user()->addresses()->orderByDesc('is_default')->latest()->get();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->name = Auth::user()->name;
        $this->phone = (string) Auth::user()->phone;
        Flux::modal('address-form')->show();
    }

    public function edit(int $id): void
    {
        $address = Auth::user()->addresses()->findOrFail($id);
        $this->editingId = $address->id;
        $this->fill($address->only('label', 'name', 'phone', 'address', 'city', 'state'));
        $this->isDefault = $address->is_default;
        Flux::modal('address-form')->show();
    }

    public function save(): void
    {
        $data = $this->validate([
            'label' => ['required', 'string', 'max:40'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9 ]{10,16}$/'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'state' => ['required', Rule::in(DeliveryZone::STATES)],
        ]);

        $user = Auth::user();
        $makeDefault = $this->isDefault || ! $user->addresses()->exists();

        if ($makeDefault) {
            $user->addresses()->update(['is_default' => false]);
        }

        $user->addresses()->updateOrCreate(['id' => $this->editingId], $data + ['is_default' => $makeDefault]);

        Flux::modal('address-form')->close();
        Flux::toast(variant: 'success', text: 'Address saved.');
        $this->resetForm();
        unset($this->addresses);
    }

    public function makeDefault(int $id): void
    {
        $user = Auth::user();
        $user->addresses()->update(['is_default' => false]);
        $user->addresses()->whereKey($id)->update(['is_default' => true]);
        unset($this->addresses);
    }

    public function delete(int $id): void
    {
        Auth::user()->addresses()->whereKey($id)->delete();
        unset($this->addresses);
        Flux::toast(text: 'Address removed.');
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'label', 'name', 'phone', 'address', 'city', 'state', 'isDefault');
        $this->resetValidation();
    }
}; ?>

<div>
    <div class="flex items-center justify-between gap-3">
        <h2 class="text-xl font-bold">My addresses</h2>
        <flux:button variant="primary" icon="plus" wire:click="create">Add address</flux:button>
    </div>

    @if ($this->addresses->isEmpty())
        <div class="mt-6 rounded-3xl border border-dashed p-10 text-center">
            <flux:icon.map-pin class="mx-auto size-10 text-zinc-300" />
            <p class="mt-3 font-semibold">No saved addresses</p>
            <p class="text-sm text-zinc-500">Save an address to check out faster.</p>
        </div>
    @else
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            @foreach ($this->addresses as $item)
                <div wire:key="address-{{ $item->id }}" class="rounded-3xl border p-5 {{ $item->is_default ? 'border-brand-400 bg-brand-50/50 dark:bg-brand-950/30' : '' }}">
                    <div class="flex items-center justify-between">
                        <p class="font-bold">{{ $item->label }}</p>
                        @if ($item->is_default)<flux:badge color="emerald" size="sm">Default</flux:badge>@endif
                    </div>
                    <p class="mt-2 text-sm">{{ $item->name }} · {{ $item->phone }}</p>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $item->oneLine() }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <flux:button size="sm" wire:click="edit({{ $item->id }})">Edit</flux:button>
                        @unless ($item->is_default)
                            <flux:button size="sm" variant="ghost" wire:click="makeDefault({{ $item->id }})">Make default</flux:button>
                        @endunless
                        <flux:button size="sm" variant="ghost" wire:click="delete({{ $item->id }})" wire:confirm="Remove this address?">Remove</flux:button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <flux:modal name="address-form" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit address' : 'New address' }}</flux:heading>
            <flux:input wire:model="label" label="Label" placeholder="Home, Office..." />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="name" label="Full name" />
                <flux:input wire:model="phone" label="Phone" type="tel" />
            </div>
            <flux:input wire:model="address" label="Street address" />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="city" label="City / area" />
                <flux:select wire:model="state" label="State">
                    <flux:select.option value="">Choose state</flux:select.option>
                    @foreach (\App\Models\DeliveryZone::STATES as $option)
                        <flux:select.option value="{{ $option }}">{{ $option }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <flux:checkbox wire:model="isDefault" label="Use as my default address" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Save address</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
