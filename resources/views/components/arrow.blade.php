@props(['direction' => 'right'])
@php
    $rotation = match ($direction) {
        'left' => 'rotate-180',
        'up' => '-rotate-90',
        'down' => 'rotate-90',
        'up-right' => '-rotate-45',
        default => '',
    };
@endphp
<x-icon name="arrow-right" stroke-width="2.5" {{ $attributes->class(['ds-arrow', $rotation]) }} />
