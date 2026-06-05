@props([
    'variant' => 'blue',
])

@php
    $variants = [
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-100 dark:bg-blue-950/50 dark:text-blue-200 dark:ring-blue-900',
        'indigo' => 'bg-indigo-50 text-indigo-700 ring-indigo-100 dark:bg-indigo-950/50 dark:text-indigo-200 dark:ring-indigo-900',
        'green' => 'bg-green-50 text-green-700 ring-green-100 dark:bg-green-950/50 dark:text-green-200 dark:ring-green-900',
        'orange' => 'bg-orange-50 text-orange-700 ring-orange-100 dark:bg-orange-950/50 dark:text-orange-200 dark:ring-orange-900',
        'red' => 'bg-red-50 text-red-700 ring-red-100 dark:bg-red-950/50 dark:text-red-200 dark:ring-red-900',
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:ring-slate-700',
        'dark' => 'bg-slate-950 text-white ring-slate-800 dark:bg-white dark:text-slate-950 dark:ring-white',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex max-w-full items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-1 text-center text-xs font-black uppercase leading-tight tracking-wider ring-1 shadow-sm '.($variants[$variant] ?? $variants['blue'])]) }}>
    {{ $slot }}
</span>
