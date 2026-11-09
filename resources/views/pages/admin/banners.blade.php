<?php

use App\Livewire\Concerns\AdminOnly;
use App\Models\Banner;
use App\Support\ImageOptimizer;
use App\Support\Media;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin'), Title('Home banners')] class extends Component {
    use AdminOnly, WithFileUploads;

    public ?int $editingId = null;

    public string $eyebrow = '';

    public string $title = '';

    public string $subtitle = '';

    public string $button_text = '';

    public string $link = '';

    public int $sort_order = 0;

    public bool $is_active = true;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $photo = null;

    /** @return Collection<int, Banner> */
    #[Computed]
    public function banners(): Collection
    {
        return Banner::query()->orderBy('sort_order')->get();
    }

    public function create(): void
    {
        $this->resetForm();
        Flux::modal('banner-form')->show();
    }

    public function edit(int $id): void
    {
        $this->resetForm();
        $banner = Banner::query()->findOrFail($id);
        $this->editingId = $banner->id;
        $this->title = $banner->title;
        $this->eyebrow = (string) $banner->eyebrow;
        $this->subtitle = (string) $banner->subtitle;
        $this->button_text = (string) $banner->button_text;
        $this->link = (string) $banner->link;
        $this->sort_order = $banner->sort_order;
        $this->is_active = $banner->is_active;
        Flux::modal('banner-form')->show();
    }

    public function save(ImageOptimizer $optimizer): void
    {
        $data = $this->validate([
            'eyebrow' => ['nullable', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:90'],
            'subtitle' => ['nullable', 'string', 'max:250'],
            'button_text' => ['nullable', 'string', 'max:30'],
            'link' => ['nullable', 'string', 'max:255', 'regex:/^(\/|https?:\/\/)/'],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
            'photo' => [$this->editingId ? 'nullable' : 'required', 'image', 'max:10240'],
        ], ['link.regex' => 'Start the link with / (a page on this site) or https://']);

        $banner = Banner::query()->findOrNew($this->editingId);
        $banner->fill(collect($data)->except('photo')->map(fn ($v) => $v === '' ? null : $v)->all());

        if ($this->photo) {
            Media::delete($banner->image);
            $banner->image = $optimizer->store($this->photo, 'banners');
        }

        $banner->save();

        Flux::modal('banner-form')->close();
        Flux::toast(variant: 'success', text: 'Banner saved.');
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $banner = Banner::query()->findOrFail($id);
        Media::delete($banner->image);
        $banner->delete();
        Flux::toast(text: 'Banner deleted.');
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'eyebrow', 'title', 'subtitle', 'button_text', 'link', 'sort_order', 'is_active', 'photo');
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Home banners</flux:heading>
            <flux:text>The big sliding pictures at the top of the home page. Wide photos (at least 1600px across) look best.</flux:text>
        </div>
        <flux:button variant="primary" icon="plus" wire:click="create">New banner</flux:button>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($this->banners as $banner)
            <div wire:key="banner-{{ $banner->id }}" class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                <div class="relative aspect-[21/9]">
                    <img src="{{ $banner->imageUrl() }}" alt="" class="size-full object-cover">
                    <div class="absolute inset-0 bg-gradient-to-r from-brand-950/80 to-transparent p-5 text-white">
                        <p class="text-xs font-bold tracking-widest text-amber-300 uppercase">{{ $banner->eyebrow }}</p>
                        <p class="mt-1 max-w-xs text-xl font-extrabold">{{ $banner->title }}</p>
                    </div>
                    @unless ($banner->is_active)<span class="absolute end-3 top-3"><flux:badge color="zinc">Hidden</flux:badge></span>@endunless
                </div>
                <div class="flex items-center justify-between gap-2 p-4">
                    <p class="truncate text-sm text-zinc-500">{{ $banner->button_text }} → {{ $banner->link ?: 'no link' }}</p>
                    <div class="flex shrink-0 gap-2">
                        <flux:button size="sm" wire:click="edit({{ $banner->id }})">Edit</flux:button>
                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $banner->id }})" wire:confirm="Delete this banner?" aria-label="Delete" />
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <flux:modal name="banner-form" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit banner' : 'New banner' }}</flux:heading>
            <flux:input wire:model="eyebrow" label="Small label (optional)" placeholder="e.g. New season" />
            <flux:input wire:model="title" label="Headline" />
            <flux:textarea wire:model="subtitle" label="Text under the headline" rows="2" />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="button_text" label="Button text" placeholder="Shop now" />
                <flux:input wire:model="link" label="Button link" placeholder="/category/skincare" />
            </div>
            <flux:input type="file" wire:model="photo" label="Photo" accept="image/*" />
            <div class="flex items-center gap-6">
                <flux:switch wire:model="is_active" label="Show" />
                <flux:input wire:model="sort_order" type="number" min="0" label="Order" class="max-w-24" />
            </div>
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
