@props(['for' => 'password'])
{{-- Live password strength bar and checklist for the password field named $for. --}}
<div
    x-data="{
        value: '',
        init() {
            const input = document.querySelector('input[name={{ $for }}]');
            if (input) {
                input.addEventListener('input', () => this.value = input.value);
            }
        },
        get checks() {
            return [
                { label: 'At least 8 characters', ok: this.value.length >= 8 },
                { label: 'A letter', ok: /[a-zA-Z]/.test(this.value) },
                { label: 'A number', ok: /[0-9]/.test(this.value) },
                { label: 'A symbol or capital letter (stronger)', ok: /[^a-zA-Z0-9]/.test(this.value) || /[A-Z]/.test(this.value) },
            ];
        },
        get score() { return this.value === '' ? 0 : this.checks.filter(c => c.ok).length },
        get label() { return ['', 'Weak', 'Fair', 'Good', 'Strong'][this.score] },
    }"
    class="-mt-2 space-y-2"
    aria-live="polite"
>
    <div class="flex items-center gap-3">
        <div class="grid h-1.5 flex-1 grid-cols-4 gap-1">
            <template x-for="i in 4" :key="i">
                <span class="rounded-full transition-colors duration-300" :class="i <= score ? ['', 'bg-red-500', 'bg-amber-500', 'bg-brand-400', 'bg-brand-600'][score] : 'bg-zinc-200 dark:bg-zinc-800'"></span>
            </template>
        </div>
        <span class="w-14 text-end text-xs font-semibold" :class="['text-zinc-400', 'text-red-600', 'text-amber-600', 'text-brand-600', 'text-brand-700'][score]" x-text="label"></span>
    </div>
    <ul class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs">
        <template x-for="check in checks" :key="check.label">
            <li class="flex items-center gap-1.5 transition-colors" :class="check.ok ? 'text-brand-700 dark:text-brand-400' : 'text-zinc-500'">
                <span class="grid size-3.5 place-items-center rounded-full text-[9px] font-bold" :class="check.ok ? 'bg-brand-600 text-white' : 'border border-zinc-300 dark:border-zinc-700'" x-text="check.ok ? '✓' : ''"></span>
                <span x-text="check.label"></span>
            </li>
        </template>
    </ul>
</div>
