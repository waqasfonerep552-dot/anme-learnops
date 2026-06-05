@props([
    'name' => 'sparkles',
    'tone' => 'blue',
    'size' => 'md',
])

@php
    $tones = [
        'blue' => 'from-blue-600 to-indigo-600 shadow-blue-900/25',
        'indigo' => 'from-indigo-600 to-violet-600 shadow-indigo-900/25',
        'orange' => 'from-orange-500 to-amber-500 shadow-orange-900/25',
        'green' => 'from-emerald-500 to-teal-500 shadow-emerald-900/20',
        'slate' => 'from-slate-800 to-slate-950 shadow-slate-900/25',
        'rose' => 'from-rose-600 to-red-600 shadow-rose-900/25',
    ];

    $sizes = [
        'sm' => 'h-10 w-10 rounded-2xl',
        'md' => 'h-12 w-12 rounded-[1.15rem]',
        'lg' => 'h-14 w-14 rounded-[1.35rem]',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'premium-icon-tile bg-gradient-to-br '.($tones[$tone] ?? $tones['blue']).' '.($sizes[$size] ?? $sizes['md'])]) }}>
    <x-ui.icon :name="$name" class="relative z-10 h-5 w-5 text-white" />
</span>
