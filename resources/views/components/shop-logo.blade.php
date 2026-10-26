@props(['label' => true, 'accented' => false])

<a href="{{ route('home') }}" {{ $attributes->class(['ds-shop-logo']) }}>
    <x-icon name="shopping-bag" class="ds-shop-logo__icon" stroke-width="2.25" />
    @if ($label)
        <span>Shop<span class="ds-shop-logo__accent">Plus</span></span>
    @endif
</a>
