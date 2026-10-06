@props(['variant' => 'primary', 'size' => 'md', 'type' => 'submit'])

@php
    $variants = ['primary' => 'ds-button--primary', 'secondary' => 'ds-button--secondary', 'danger' => 'ds-button--danger', 'success' => 'ds-button--success'];
    $sizes = ['sm' => 'ds-button--sm', 'md' => '', 'lg' => 'ds-button--lg'];
@endphp

<button {{ $attributes->merge(['type' => $type])->class(['ds-button', $variants[$variant] ?? $variants['primary'], $sizes[$size] ?? '']) }}>
    {{ $slot }}
</button>
