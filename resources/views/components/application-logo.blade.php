@props([
    'variant' => 'mark',
])

@if($variant === 'horizontal')
    <img
        src="{{ asset('brand/anme-learnops-logo.svg') }}"
        alt="{{ config('app.name', 'ANME LearnOps') }}"
        {{ $attributes->merge(['class' => 'h-12 w-auto']) }}
    >
@else
    <img
        src="{{ asset('brand/anme-learnops-mark.svg') }}"
        alt="{{ config('app.name', 'ANME LearnOps') }}"
        {{ $attributes->merge(['class' => 'h-12 w-12 rounded-[1.25rem] shadow-lg shadow-blue-900/25']) }}
    >
@endif
