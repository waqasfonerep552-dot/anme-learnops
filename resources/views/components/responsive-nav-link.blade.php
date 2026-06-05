@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-2xl bg-slate-950 px-4 py-3 text-start text-base font-black text-white transition dark:bg-blue-700'
            : 'block w-full rounded-2xl px-4 py-3 text-start text-base font-black text-slate-700 transition hover:bg-blue-50 hover:text-blue-700 dark:text-slate-200 dark:hover:bg-slate-800';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
