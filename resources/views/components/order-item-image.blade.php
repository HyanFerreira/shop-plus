@props(['item'])
@php
    $disk = Storage::disk('public');
    $product = $item?->product;
    $photo = $product && $product->sku === $item->sku
        ? $product->images->first(fn ($image) => $disk->exists($image->path)) : null;
    $demo = $item ? (config('demo-images.products')[$item->sku] ?? null) : null;
    $url = $photo ? $disk->url($photo->path) : ($demo ? asset('images/demo-products/'.$demo['file']) : null);
@endphp
<div {{ $attributes->class(['order-item-image']) }} x-data="{ imageFailed: false }">
    @if ($url)
        <img src="{{ $url }}" alt="Imagem ilustrativa de {{ $item->product_name }}" width="96" height="96" loading="lazy" decoding="async" x-show="!imageFailed" x-init="imageFailed = $el.complete && $el.naturalWidth === 0" x-on:error="imageFailed = true">
    @endif
    <span class="order-item-image__fallback" @if($url) x-cloak x-show="imageFailed" @endif><x-icon name="shopping-bag" class="size-6" /><span>Imagem indisponível</span></span>
</div>
