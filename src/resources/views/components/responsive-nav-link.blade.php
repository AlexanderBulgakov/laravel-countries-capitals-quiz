@props(['active'])

@php
$classes = ($active ?? false)
            ? 'py-2 hover:text-gray-900'
            : 'py-2 hover:text-gray-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
