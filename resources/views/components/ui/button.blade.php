@props([
    'href' => null,
    'variant' => 'primary',
    'type' => 'button',
    'disabled' => false,
])

@php
    $base = 'inline-flex min-h-11 max-w-full items-center justify-center gap-2 whitespace-nowrap rounded-2xl px-5 py-3 text-center text-sm font-black leading-5 transition focus:outline-none focus:ring-4 focus:ring-blue-200 disabled:pointer-events-none disabled:opacity-60';
    $variants = [
        'primary' => 'bg-gradient-to-r from-blue-700 to-indigo-700 text-white shadow-lg shadow-blue-900/20 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-blue-900/25',
        'accent' => 'bg-gradient-to-r from-indigo-700 to-purple-700 text-white shadow-lg shadow-indigo-900/20 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-indigo-900/25',
        'orange' => 'bg-gradient-to-r from-orange-500 to-amber-500 text-white shadow-lg shadow-orange-900/20 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-orange-900/25',
        'secondary' => 'border border-slate-200 bg-white/90 text-slate-700 shadow-sm backdrop-blur hover:-translate-y-0.5 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-800 dark:bg-slate-900/80 dark:text-slate-100 dark:hover:bg-slate-800',
        'dark' => 'bg-slate-950 text-white shadow-lg shadow-slate-900/20 hover:-translate-y-0.5 hover:bg-slate-800 hover:shadow-xl',
        'danger' => 'bg-gradient-to-r from-red-600 to-rose-600 text-white shadow-lg shadow-red-900/20 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-red-900/25',
    ];
    $classes = $base.' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
