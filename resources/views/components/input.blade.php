@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->class(['ds-input']) !!}>
