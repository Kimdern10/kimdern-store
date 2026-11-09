<?php

use App\Livewire\Concerns\AdminOnly;
use App\Models\Category;
use App\Support\ImageOptimizer;
use App\Support\Media;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::admin'), Title('Categories')] class extends Component {
    use AdminOnly, WithFileUploads;

    public ?int $editingId = null;

    public string $name = '';

    public string $department = 'beauty';

    public string $description = '';

    public int $sort_order = 0;

    public bool $is_active = true;

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    public $photo = null;

    /** @return Collection<int, Category> */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->withCount('products')->orderBy('sort_order')->orderBy('name')->get();
    }

    public function create(): void
    {
        $this->resetForm();
        Flux::modal('category-form')->show();
    }

    public function edit(int $id): void
    {
        $this->resetForm();
        $category = Category::query()->findOrFail($id);
        $this->editingId = $category->id;
        $this->fill($category->only('name', 'department', 'sort_order', 'is_active'));
        $this->description = (string) $category->description;
        Flux::modal('category-form')->show();
    }

    public function save(ImageOptimizer $optimizer): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'department' => ['required', Rule::in(array_keys(Category::DEPARTMENTS))],
            'description' => ['nullable', 'string', 'max:300'],
            'sort_order' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
            'photo' => ['nullable', 'image', 'max:8192'],
        ]);

        $category = Category::query()->findOrNew($this->editingId);
        $slug = Str::slug($data['name']);

        if (Category::query()->where('slug', $slug)->whereKeyNot($category->id)->exists()) {
            $this->addError('name', 'Another category already has this name.');

            return;
        }

        $category->fill(collect($data)->except('photo')->all() + ['slug' => $slug]);

        if ($this->photo) {
            Media::delete($category->image);
            $category->image = $optimizer->store($this->photo, 'categories');
        }

        $category->save();

        Flux::modal('category-form')->close();
        Flux::toast(variant: 'success', text: 'Category saved.');
        $this->resetForm();
    }

    public function delete(int $id): void
    {
        $category = Category::query()->withCount('products')->findOrFail($id);

        if ($category->products_count > 0) {
            Flux::toast(variant: 'danger', text: 'Move or delete the products in this category first.');

            return;
        }

        Media::delete($category->image);
        $category->delete();
        Flux::toast(text: 'Category deleted.');
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'name', 'department', 'description', 'sort_order', 'is_active', 'photo');
        $this->resetValidation();
    }
}; ?>

<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="xl">Categories</flux:heading>
        <flux:button variant="primary" icon="plus" wire:click="create">Add category</flux:button>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($this->categories as $category)
            <div wire:key="cat-{{ $category->id }}" class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                <div class="relative aspect-[16/9] bg-zinc-100 dark:bg-zinc-800">
                    @if ($category->imageUrl())<img src="{{ $category->imageUrl() }}" alt="" class="size-full object-cover">@endif
                    @unless ($category->is_active)<span class="absolute start-2 top-2"><flux:badge size="sm" color="zinc">Hidden</flux:badge></span>@endunless
                </div>
                <div class="p-4">
                    <p class="font-semibold">{{ $category->name }}</p>
                    <p class="text-xs text-zinc-500">{{ \App\Models\Category::DEPARTMENTS[$category->department] ?? '' }} · {{ $category->products_count }} products</p>
                    <div class="mt-3 flex gap-2">
                        <flux:button size="sm" wire:click="edit({{ $category->id }})">Edit</flux:button>
                        <flux:button size="sm" variant="ghost" wire:click="delete({{ $category->id }})" wire:confirm="Delete {{ $category->name }}?">Delete</flux:button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <flux:modal name="category-form" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-4">
            <flux:heading size="lg">{{ $editingId ? 'Edit category' : 'New category' }}</flux:heading>
            <flux:input wire:model="name" label="Name" />
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:select wire:model="department" label="Department">
                    @foreach (\App\Models\Category::DEPARTMENTS as $key => $label)
                        <flux:select.option :value="$key">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input wire:model="sort_order" type="number" min="0" label="Order" description="Lower shows first" />
            </div>
            <flux:textarea wire:model="description" label="Short description" rows="2" />
            <flux:input type="file" wire:model="photo" label="Photo" accept="image/*" />
            <flux:switch wire:model="is_active" label="Show on the shop" />
            <div class="flex justify-end gap-2">
                <flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close>
                <flux:button type="submit" variant="primary">Save</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
