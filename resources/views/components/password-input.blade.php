@props(['disabled' => false])

<div x-data="{ visible: false }" class="relative">
    <input
        @disabled($disabled)
        type="password"
        x-bind:type="visible ? 'text' : 'password'"
        {{ $attributes->merge(['class' => 'w-full min-w-0 rounded-xl border-slate-200 bg-white/90 px-4 py-3 pr-12 text-sm font-semibold text-slate-800 shadow-sm transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100 disabled:opacity-60 dark:border-slate-700 dark:bg-slate-900/80 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-950']) }}
    >

    <button
        type="button"
        x-on:click="visible = ! visible"
        class="absolute right-3 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:hover:bg-slate-800 dark:hover:text-slate-200"
        x-bind:aria-label="visible ? 'Hide password' : 'Show password'"
    >
        <svg x-show="! visible" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-6.75 9.75-6.75S21.75 12 21.75 12 18 18.75 12 18.75 2.25 12 2.25 12Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
        </svg>
        <svg x-show="visible" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223C2.84 9.498 2.25 12 2.25 12s3.75 6.75 9.75 6.75c1.56 0 2.948-.456 4.146-1.1M6.53 6.53A9.57 9.57 0 0 1 12 5.25c6 0 9.75 6.75 9.75 6.75a17.92 17.92 0 0 1-2.374 3.426M6.53 6.53 3.75 3.75m2.78 2.78 10.94 10.94m0 0 2.78 2.78" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 9.88a3 3 0 0 0 4.24 4.24" />
        </svg>
    </button>
</div>
