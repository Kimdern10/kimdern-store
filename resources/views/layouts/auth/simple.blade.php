<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-gradient-to-br from-brand-50 via-white to-amber-50 font-sans antialiased dark:from-brand-950 dark:via-zinc-950 dark:to-zinc-900">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 p-4 sm:p-10">
            <div class="flex w-full max-w-md flex-col gap-6 rounded-3xl bg-white/80 p-6 shadow-xl shadow-brand-900/5 ring-1 ring-zinc-900/5 backdrop-blur sm:p-10 dark:bg-zinc-900/80 dark:ring-white/10">
                <div class="flex justify-center">
                    <x-store-logo />
                </div>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
            <a href="{{ route('home') }}" class="text-sm font-semibold text-brand-700 hover:underline dark:text-brand-400">← Back to the shop</a>
        </div>

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
