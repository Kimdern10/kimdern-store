<?php

use App\Livewire\Concerns\AdminOnly;
use App\Models\Coupon;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::admin'), Title('Coupons')] class extends Component {
    use AdminOnly;

    public ?int $editingId = null;

    public string $code = '';

    public string $description = '';

    public string $type = 'percent';

    public string $value = '';

    public string $min_subtotal = '';

    public string $max_uses = '';

    public string $starts_at = '';

    public string $expires_at = '';

    public bool $is_active = true;

    /** @return Collection<int, Coupon> */
    #[Computed]
    public function coupons(): Collection
    {
        return Coupon::query()->latest()->get();
    }

    public function create(): void
    {
        $this->resetForm();
        Flux::modal('coupon-form')->show();
    }

    public function edit(int $id): void
    {
        $this->resetForm();
        $coupon = Coupon::query()->findOrFail($id);
        $this->editingId = $coupon->id;
        $this->code = $coupon->code;
        $this->description = (string) $coupon->description;
        $this->type = $coupon->type;
        $this->value = (string) ($coupon->type === 'percent' ? $coupon->value : $coupon->value / 100);
        $this->min_subtotal = $coupon->min_subtotal ? (string) ($coupon->min_subtotal / 100) : '';
        $this->max_uses = (string) $coupon->max_uses;
        $this->starts_at = (string) $coupon->starts_at?->format('Y-m-d');
        $this->expires_at = (string) $coupon->expires_at?->format('Y-m-d');
        $this->is_active = $coupon->is_active;
        Flux::modal('coupon-form')->show();
    }

    public function save(): void
    {
        $this->code = strtoupper(trim($this->code));

        $data = $this->validate([
            'code' => ['required', 'alpha_dash', 'max:30', Rule::unique('coupons', 'code')->ignore($this->editingId)],
            'description' => ['nullable', 'string', 'max:150'],
            'type' => ['required', Rule::in(['percent', 'fixed'])],
            'value' => ['required', 'numeric', 'min:1', $this->type === 'percent' ? 'max:100' : 'max:10000000'],
            'min_subtotal' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
        ]);

        Coupon::query()->updateOrCreate(['id' => $this->editingId], [
            'code' => $data['code'],
            'description' => $data['description'] ?: null,
            'type' => $data['type'],
            'value' => $data['type'] === 'percent' ? (int) $data['value'] : (int) round($data['value'] * 100),
            'min_subtotal' => (int) round(((float) ($data['min_subtotal'] ?: 0)) * 100),
            'max_uses' => $data['max_uses'] ?: null,
            'starts_at' => $data['starts_at'] ?: null,
            'expires_at' => $data['expires_at'] ? \Illuminate\Support\Carbon::parse($data['expires_at'])->endOfDay() : null,
            'is_active' => $data['is_active'],
        ]);

        Flux::modal('coupon-form')->close();
        Flux::toast(variant: 'success', text: 'Coupon saved.');
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        Coupon::query()->whereKey($id)->delete();
        Flux::toast(text: 'Coupon deleted.');
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'code', 'description', 'type', 'value', 'min_subtotal', 'max_uses', 'starts_at', 'expires_at', 'is_active');
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Coupons</flux:heading>
            <flux:text>Discount codes customers type at checkout.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create">New coupon</flux:button>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead class="border-b border-zinc-100 bg-zinc-50 text-xs tracking-wide text-zinc-500 uppercase dark:border-zinc-800 dark:bg-zinc-950/40">
                <tr><th class="px-5 py-3 text-start">Code</th><th class="px-5 py-3 text-start">Discount</th><th class="px-5 py-3 text-start">Used</th><th class="px-5 py-3 text-start">Valid</th><th class="px-5 py-3 text-start">Status</th><th class="px-5 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                @forelse ($this->coupons as $coupon)
                    <tr wire:key="coupon-{{ $coupon->id }}">
                        <td class="px-5 py-3"><span class="rounded-lg bg-amber-100 px-2 py-1 font-mono font-bold text-amber-900 dark:bg-amber-900/40 dark:text-amber-200">{{ $coupon->code }}</span><p class="mt-1 text-xs text-zinc-500">{{ $coupon->description }}</p></td>
                        <td class="px-5 py-3 font-semibold">{{ $coupon->summary() }}@if ($coupon->min_subtotal)<p class="text-xs font-normal text-zinc-500">Min. spend {{ naira($coupon->min_subtotal) }}</p>@endif</td>
                        <td class="px-5 py-3">{{ $coupon->used_count }}{{ $coupon->max_uses ? ' / '.$coupon->max_uses : '' }}</td>
                        <td class="px-5 py-3 text-xs text-zinc-500">{{ $coupon->starts_at?->format('j M Y') ?? 'Now' }} → {{ $coupon->expires_at?->format('j M Y') ?? 'No end' }}</td>
                        <td class="px-5 py-3">
                            @php($problem = $coupon->problemWith(PHP_INT_MAX))
                            <flux:badge size="sm" :color="$problem ? 'zinc' : 'emerald'">{{ $problem ? 'Not usable' : 'Active' }}</flux:badge>
                        </td>
                        <td class="px-5 py-3 text-end whitespace-nowrap">
                            <flux:button size="sm" wire:click="edit({{ $coupon->id }})">Edit</flux:button>
                            <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $coupon->id }})" wire:confirm="Delete {{ $coupon->code }}?" aria-label="Delete" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-zinc-500">No coupons yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <flux:modal name="coupon-form" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit coupon' : 'New coupon' }}</flux:heading>
            <flux:input wire:model="code" label="Code" placeholder="e.g. SALLAH20" class="uppercase" />
            <flux:input wire:model="description" label="Note (optional)" placeholder="What it's for" />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model.live="type" label="Type">
                    <flux:select.option value="percent">Percentage off</flux:select.option>
                    <flux:select.option value="fixed">Naira amount off</flux:select.option>
                </flux:select>
                <flux:input wire:model="value" type="number" step="0.01" :label="$type === 'percent' ? 'Percent (%)' : 'Amount (₦)'" />
                <flux:input wire:model="min_subtotal" type="number" step="0.01" label="Minimum spend (₦)" placeholder="None" />
                <flux:input wire:model="max_uses" type="number" label="Max uses" placeholder="Unlimited" />
                <flux:input wire:model="starts_at" type="date" label="Starts" />
                <flux:input wire:model="expires_at" type="date" label="Ends" />
            </div>
            <flux:switch wire:model="is_active" label="Active" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
