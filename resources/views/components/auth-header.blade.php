@props([
    'title',
    'description',
    'icon' => null,
])

<div class="flex w-full flex-col gap-2">
    @if ($icon)
        <span class="mb-2 grid size-14 place-items-center rounded-2xl bg-brand-100 text-brand-700 dark:bg-brand-900 dark:text-brand-300">
            <flux:icon :name="$icon" class="size-7" />
        </span>
    @endif
    <h1 class="text-3xl font-extrabold tracking-tight text-brand-950 dark:text-white">{{ $title }}</h1>
    <p class="text-zinc-600 dark:text-zinc-400">{{ $description }}</p>
</div>
