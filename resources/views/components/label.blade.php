@props(['value'])

<label {{ $attributes->class(['ds-label']) }}>
    {{ $value ?? $slot }}
</label>
