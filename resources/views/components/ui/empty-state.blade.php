@props([
    'title',
    'description' => null,
    'action' => null,
    'href' => null,
    'icon' => 'sparkles',
    'tone' => 'blue',
])

<div {{ $attributes->merge(['class' => 'relative overflow-hidden rounded-[1.75rem] border border-dashed border-blue-200 bg-white/80 p-10 text-center shadow-xl shadow-slate-200/50 backdrop-blur-xl dark:border-blue-900/60 dark:bg-slate-900/80 dark:shadow-slate-950/40']) }}>
    <div class="pointer-events-none absolute -right-14 -top-14 h-32 w-32 rounded-full bg-blue-500/10 blur-3xl"></div>
    <x-ui.icon-tile :name="$icon" :tone="$tone" size="lg" class="mx-auto" />
    <h3 class="mt-5 text-2xl font-black text-slate-950 dark:text-white">{{ $title }}</h3>
    @if($description)
        <p class="mx-auto mt-2 max-w-md text-slate-600 dark:text-slate-300">{{ $description }}</p>
    @endif
    @if($action && $href)
        <x-ui.button :href="$href" class="mt-6">{{ $action }}</x-ui.button>
    @endif
</div>
