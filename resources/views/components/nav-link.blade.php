@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center rounded-xl bg-slate-950 px-4 py-2 text-sm font-black text-white shadow-md transition dark:bg-blue-700'
            : 'inline-flex items-center rounded-xl px-4 py-2 text-sm font-black text-slate-600 transition hover:bg-blue-50 hover:text-blue-700 dark:text-slate-300 dark:hover:bg-slate-800';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
