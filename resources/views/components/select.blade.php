@props(['disabled' => false])

<select @disabled($disabled) {{ $attributes->class(['ds-input', 'ds-select']) }}>
    {{ $slot }}
</select>
