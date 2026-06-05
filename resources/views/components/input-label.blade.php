@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-black text-slate-700 dark:text-slate-200']) }}>
    {{ $value ?? $slot }}
</label>
