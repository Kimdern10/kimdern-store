@props(['rating' => 0, 'count' => null, 'size' => 'size-4'])
<div {{ $attributes->class('flex items-center gap-1') }}>
    <div class="flex text-amber-400" role="img" aria-label="Rated {{ number_format($rating, 1) }} out of 5">
        @for ($i = 1; $i <= 5; $i++)
            <svg viewBox="0 0 20 20" class="{{ $size }} {{ $rating >= $i - 0.25 ? 'fill-current' : ($rating >= $i - 0.75 ? 'fill-current opacity-60' : 'fill-zinc-200 dark:fill-zinc-700') }}" aria-hidden="true">
                <path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.8l-5.2 2.8 1-5.8L1.5 7.7l5.9-.9L10 1.5z" />
            </svg>
        @endfor
    </div>
    @if ($count !== null)
        <span class="text-xs text-zinc-500">({{ $count }})</span>
    @endif
</div>
