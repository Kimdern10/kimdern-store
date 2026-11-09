<?php

use App\Livewire\Concerns\AdminOnly;
use App\Models\DeliveryZone;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin'), Title('Delivery zones')] class extends Component {
    use AdminOnly;

    public ?int $editingId = null;

    public string $name = '';

    /** @var list<string> */
    public array $states = [];

    public string $fee = '';

    public string $free_over = '';

    public string $eta = '';

    public int $sort_order = 0;

    public bool $is_active = true;

    /** @return Collection<int, DeliveryZone> */
    #[Computed]
    public function zones(): Collection
    {
        return DeliveryZone::query()->orderBy('sort_order')->get();
    }

    /**
     * States that no active zone delivers to.
     *
     * @return list<string>
     */
    #[Computed]
    public function uncovered(): array
    {
        $covered = $this->zones->where('is_active', true)->pluck('states')->flatten()->all();

        return array_values(array_diff(DeliveryZone::STATES, $covered));
    }

    public function create(): void
    {
        $this->resetForm();
        Flux::modal('zone-form')->show();
    }

    public function edit(int $id): void
    {
        $this->resetForm();
        $zone = DeliveryZone::query()->findOrFail($id);
        $this->editingId = $zone->id;
        $this->fill($zone->only('name', 'states', 'eta', 'sort_order', 'is_active'));
        $this->eta = (string) $zone->eta;
        $this->fee = (string) ($zone->fee / 100);
        $this->free_over = $zone->free_over ? (string) ($zone->free_over / 100) : '';
        Flux::modal('zone-form')->show();
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'states' => ['required', 'array', 'min:1'],
            'states.*' => [Rule::in(DeliveryZone::STATES)],
            'fee' => ['required', 'numeric', 'min:0'],
            'free_over' => ['nullable', 'numeric', 'min:0'],
            'eta' => ['nullable', 'string', 'max:60'],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
        ], ['states.required' => 'Tick at least one state.']);

        DeliveryZone::query()->updateOrCreate(['id' => $this->editingId], [
            'name' => $data['name'],
            'states' => array_values($data['states']),
            'fee' => (int) round($data['fee'] * 100),
            'free_over' => filled($data['free_over']) ? (int) round($data['free_over'] * 100) : null,
            'eta' => $data['eta'] ?: null,
            'sort_order' => $data['sort_order'],
            'is_active' => $data['is_active'],
        ]);

        Flux::modal('zone-form')->close();
        Flux::toast(variant: 'success', text: 'Delivery zone saved.');
        $this->resetForm();
        unset($this->zones, $this->uncovered);
    }

    public function delete(int $id): void
    {
        DeliveryZone::query()->whereKey($id)->delete();
        unset($this->zones, $this->uncovered);
        Flux::toast(text: 'Zone deleted.');
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'name', 'states', 'fee', 'free_over', 'eta', 'sort_order', 'is_active');
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Delivery zones</flux:heading>
            <flux:text>Group states that share a delivery fee. The fee is added at checkout when the customer picks their state.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create">New zone</flux:button>
    </div>

    @if ($this->uncovered)
        <flux:callout variant="warning" icon="exclamation-triangle" heading="Customers in these states can't check out yet" :text="implode(', ', $this->uncovered)" />
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($this->zones as $zone)
            <div wire:key="zone-{{ $zone->id }}" class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-lg font-semibold">{{ $zone->name }} @unless ($zone->is_active)<flux:badge size="sm" color="zinc">Off</flux:badge>@endunless</p>
                        <p class="text-sm text-zinc-500">{{ $zone->eta }}</p>
                    </div>
                    <div class="text-end">
                        <p class="text-xl font-extrabold">{{ naira($zone->fee) }}</p>
                        @if ($zone->free_over)<p class="text-xs text-brand-600">Free over {{ naira($zone->free_over) }}</p>@endif
                    </div>
                </div>
                <p class="mt-3 text-sm text-zinc-600 dark:text-zinc-400">{{ implode(', ', $zone->states) }}</p>
                <div class="mt-4 flex gap-2">
                    <flux:button size="sm" wire:click="edit({{ $zone->id }})">Edit</flux:button>
                    <flux:button size="sm" variant="ghost" wire:click="delete({{ $zone->id }})" wire:confirm="Delete {{ $zone->name }}?">Delete</flux:button>
                </div>
            </div>
        @endforeach
    </div>

    <flux:modal name="zone-form" class="w-full max-w-2xl">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit zone' : 'New zone' }}</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="name" label="Zone name" placeholder="e.g. South West" />
                <flux:input wire:model="eta" label="Delivery time" placeholder="e.g. 2–3 working days" />
                <flux:input wire:model="fee" type="number" step="0.01" min="0" label="Fee (₦)" />
                <flux:input wire:model="free_over" type="number" step="0.01" min="0" label="Free delivery over (₦)" placeholder="Never free" />
            </div>
            <fieldset>
                <legend class="text-sm font-medium">States in this zone</legend>
                <div class="mt-2 grid max-h-64 grid-cols-2 gap-2 overflow-y-auto rounded-xl border border-zinc-200 p-3 sm:grid-cols-3 dark:border-zinc-700">
                    @foreach (\App\Models\DeliveryZone::STATES as $state)
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="states" value="{{ $state }}" class="rounded border-zinc-300 text-brand-700"> {{ $state }}</label>
                    @endforeach
                </div>
                @error('states')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </fieldset>
            <div class="flex items-center gap-6">
                <flux:switch wire:model="is_active" label="Active" />
                <flux:input wire:model="sort_order" type="number" min="0" label="Order" class="max-w-24" />
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
