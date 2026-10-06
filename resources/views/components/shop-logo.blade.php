@props(['label' => true])

<a href="{{ route('home') }}" {{ $attributes->class(['inline-flex items-center gap-2 text-xl font-black tracking-tight text-slate-900']) }}>
    <x-icon name="shopping-bag" class="size-6 text-blue-600" stroke-width="2.25" />
    @if ($label)<span>ShopPlus</span>@endif
</a>
