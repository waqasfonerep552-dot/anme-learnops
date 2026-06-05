@props([
    'label',
    'value',
    'description' => null,
    'tone' => 'blue',
    'icon' => null,
])

@php
    $tones = [
        'blue' => 'text-blue-700',
        'green' => 'text-green-700',
        'orange' => 'text-orange-600',
        'indigo' => 'text-indigo-700',
        'slate' => 'text-slate-950',
    ];
    $toneClass = $tones[$tone] ?? $tones['blue'];
    $barTones = [
        'blue' => 'from-blue-700 to-cyan-400',
        'green' => 'from-emerald-600 to-green-400',
        'orange' => 'from-orange-500 to-amber-400',
        'indigo' => 'from-indigo-700 to-violet-400',
        'slate' => 'from-slate-900 to-slate-500 dark:from-slate-200 dark:to-slate-500',
    ];
    $barClass = $barTones[$tone] ?? $barTones['blue'];
    $defaultIcons = [
        'students' => 'students',
        'revenue' => 'payment',
        'enrollments' => 'academy',
        'courses' => 'courses',
        'orders' => 'orders',
    ];
    $iconName = $icon ?? ($defaultIcons[str($label)->lower()->toString()] ?? 'sparkles');
@endphp

<x-ui.card padding="p-5" class="dashboard-stat-card lift-card hover:shadow-slate-300/70 dark:hover:shadow-slate-950/70">
    <div class="relative z-10 flex h-full flex-col">
        <div class="flex items-start justify-between gap-3">
            <x-ui.icon-tile :name="$iconName" :tone="$tone" size="sm" />
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-widest text-slate-500 dark:bg-slate-950 dark:text-slate-400">Live</span>
        </div>
        <p class="mt-4 text-[10px] font-black uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">{{ $label }}</p>
        <p class="mt-2 text-3xl font-black tracking-[-0.035em] {{ $toneClass }}">{{ $value }}</p>
        @if($description)
            <p class="mt-1.5 text-xs font-semibold leading-5 text-slate-500 dark:text-slate-400">{{ $description }}</p>
        @endif
        <div class="mt-auto pt-4">
            <div class="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-950">
                <div class="h-full w-2/3 rounded-full bg-gradient-to-r {{ $barClass }}"></div>
            </div>
        </div>
    </div>
</x-ui.card>
