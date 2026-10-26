@props(['product', 'showBrand' => false, 'showAvailability' => false, 'quickActions' => false, 'cartContext' => 'product'])

@php
    $disk = Storage::disk('public');
    $photo = $product->images->first(fn ($image) => $disk->exists($image->path));
    $demo = config('demo-images.products')[$product->sku] ?? null;
    $hasPhoto = (bool) $photo;
    $imageUrl = $hasPhoto
        ? $disk->url($photo->path)
        : ($demo ? asset('images/demo-products/'.$demo['file']) : null);
    $imageAlt = $hasPhoto ? ($photo->alt_text ?: $product->name) : ($demo['alt'] ?? '');
@endphp

<article {{ $attributes->class(['home-product-card', 'home-product-card--quick-actions' => $quickActions]) }}>
    <x-favorite-button :product="$product" icon="heart-square" class="favorite-control--card" />
    <a href="{{ route('catalog.show', $product->slug) }}" class="home-product-card__link">
        <div class="home-product-card__media" x-data="{ imageFailed: false }">
            @if ($imageUrl)
                <img src="{{ $imageUrl }}" alt="{{ $imageAlt }}" width="640" height="480" loading="lazy" decoding="async" x-show="!imageFailed" x-on:error="imageFailed = true">
            @endif
            <div class="home-product-card__fallback" @if($imageUrl) x-cloak x-show="imageFailed" @endif>
                <x-icon name="shopping-bag" class="size-10" />
                <span>Imagem indisponível</span>
            </div>
        </div>
        <div class="home-product-card__info">
            @if ($showBrand && $product->brand !== '')
                <p class="home-product-card__brand">{{ $product->brand }}</p>
            @endif
            <h3>{{ $product->name }}</h3>
            <p class="home-product-card__price">{{ \App\Domain\Catalog\Price::fromCents($product->price_cents)->brl() }}</p>
            @if($showAvailability && max(0, ($product->inventoryItem?->on_hand ?? 0) - ($product->inventoryItem?->reserved ?? 0)) === 0)
                <p class="home-product-card__count">Indisponível no momento</p>
            @endif
            @if ($product->rating_count > 0)
                <p class="home-product-card__rating">
                    <span aria-hidden="true">★</span>
                    <span class="sr-only">Avaliação </span>{{ number_format((float) $product->rating_average, 1, ',', '.') }}
                    <span class="sr-only"> de 5, </span>
                    <span class="home-product-card__count">({{ $product->rating_count }})<span class="sr-only"> avaliações</span></span>
                </p>
            @else
                <p class="home-product-card__count">Sem avaliações</p>
            @endif
        </div>
    </a>
</article>
