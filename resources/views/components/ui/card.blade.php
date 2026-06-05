@props([
    'padding' => 'p-6',
    'dark' => false,
])

<div {{ $attributes->merge([
    'class' => ($dark
        ? 'premium-dark-surface '
        : 'premium-surface '
    ).$padding,
]) }}>
    {{ $slot }}
</div>
