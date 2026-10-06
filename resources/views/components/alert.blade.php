@props(['variant' => 'info'])

<div role="alert" {{ $attributes->class(['ds-alert', 'ds-alert--' . $variant]) }}>{{ $slot }}</div>
