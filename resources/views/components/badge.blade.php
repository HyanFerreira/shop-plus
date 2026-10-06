@props(['variant' => 'neutral'])

<span {{ $attributes->class(['ds-badge', 'ds-badge--' . $variant]) }}>{{ $slot }}</span>
