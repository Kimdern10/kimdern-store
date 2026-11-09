@php($gradientId = 'kds-mark-'.\Illuminate\Support\Str::random(6))
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" role="img" aria-hidden="true" {{ $attributes }}>
    <defs>
        {{-- A unique id per logo: a hidden copy of the logo elsewhere on the page must not hide this one's colour. --}}
        <linearGradient id="{{ $gradientId }}" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#059669" />
            <stop offset="1" stop-color="#064e3b" />
        </linearGradient>
    </defs>
    <rect width="40" height="40" rx="12" fill="url(#{{ $gradientId }})" />
    <path d="M13 10.5h4.4v8.2l7.3-8.2h5.3l-7.6 8.3 8 10.7h-5.4l-5.7-7.9-1.9 2v5.9H13z" fill="#fff" />
    <circle cx="30.5" cy="29.5" r="3" fill="#f59e0b" />
</svg>
