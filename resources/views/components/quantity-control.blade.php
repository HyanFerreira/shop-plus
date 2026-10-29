<div {{ $attributes->class(['product-quantity']) }}>
    {{ $decrement }}
    {{ $slot }}
    {{ $increment }}
</div>
