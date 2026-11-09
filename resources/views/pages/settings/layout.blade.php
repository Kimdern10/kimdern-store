<div>
    <h2 class="text-xl font-bold">{{ $heading ?? '' }}</h2>
    <p class="mt-1 text-sm text-zinc-500">{{ $subheading ?? '' }}</p>

    <div class="mt-6 w-full max-w-lg rounded-3xl border p-6">
        {{ $slot }}
    </div>
</div>
