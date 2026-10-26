<x-storefront-layout>
    @php
        $disk = Storage::disk('public');
        $gallery = $product->images->filter(fn ($image) => $disk->exists($image->path))->unique('path')->map(fn ($image) => [
            'url' => $disk->url($image->path), 'alt' => $image->alt_text ?: $product->name,
        ])->values()->all();
        $demo = config('demo-images.products')[$product->sku] ?? null;
        if ($gallery === [] && $demo) $gallery[] = ['url' => asset('images/demo-products/'.$demo['file']), 'alt' => $demo['alt']];
    @endphp
    <main id="storefront-content" tabindex="-1" class="product-page">
        <div class="ds-storefront-container product-container">
            <nav class="product-breadcrumb" aria-label="Caminho de navegação">
                <a href="{{ route('home') }}">Início</a><span aria-hidden="true">›</span>
                <a href="{{ route('catalog.index', ['category' => $product->category->slug]) }}">{{ $product->category->name }}</a><span aria-hidden="true">›</span>
                <span aria-current="page">{{ $product->name }}</span>
            </nav>
            <article class="product-overview ds-panel">
                <section class="product-gallery {{ count($gallery) > 0 ? 'product-gallery--multiple' : '' }}" aria-label="Imagens do produto" x-data="{ selected: 0, failed: {} }">
                    <div id="product-gallery-stage" class="product-gallery__stage">
                        @foreach($gallery as $index => $image)
                            <img src="{{ $image['url'] }}" alt="{{ $image['alt'] }}" width="640" height="480" decoding="async"
                                @if($index > 0) x-cloak @endif x-show="selected === {{ $index }} && !failed[{{ $index }}]" x-on:error="failed[{{ $index }}] = true"
                                x-init="if ($el.complete && $el.naturalWidth === 0) failed[{{ $index }}] = true">
                        @endforeach
                        <div class="product-gallery__fallback" @if(count($gallery)) x-cloak x-show="failed[selected]" @endif>
                            <x-icon name="shopping-bag" /><span>Imagem indisponível</span>@if($gallery === [])<span class="sr-only">Sem imagem</span>@endif
                        </div>
                    </div>
                    @if(count($gallery) > 0)
                        <div id="product-thumbnails" class="product-thumbnails" x-ref="thumbnails" role="group" aria-label="Selecionar imagem"
                            @keydown.right.prevent="selected = (selected + 1) % {{ count($gallery) }}; $refs.thumbnails.querySelectorAll('button')[selected].focus()"
                            @keydown.left.prevent="selected = (selected + {{ count($gallery) - 1 }}) % {{ count($gallery) }}; $refs.thumbnails.querySelectorAll('button')[selected].focus()"
                            @keydown.down.prevent="selected = (selected + 1) % {{ count($gallery) }}; $refs.thumbnails.querySelectorAll('button')[selected].focus()"
                            @keydown.up.prevent="selected = (selected + {{ count($gallery) - 1 }}) % {{ count($gallery) }}; $refs.thumbnails.querySelectorAll('button')[selected].focus()"
                            @keydown.home.prevent="selected = 0; $refs.thumbnails.querySelectorAll('button')[selected].focus()"
                            @keydown.end.prevent="selected = {{ count($gallery) - 1 }}; $refs.thumbnails.querySelectorAll('button')[selected].focus()">
                            @foreach($gallery as $index => $image)
                                <button type="button" @click="selected = {{ $index }}" aria-pressed="{{ $index === 0 ? 'true' : 'false' }}" x-bind:aria-pressed="(selected === {{ $index }}).toString()" aria-controls="product-gallery-stage" aria-label="Ver imagem {{ $index + 1 }} de {{ count($gallery) }}">
                                    <img src="{{ $image['url'] }}" alt="" width="80" height="60" decoding="async" x-show="!failed[{{ $index }}]" x-on:error="failed[{{ $index }}] = true">
                                    <span x-cloak x-show="failed[{{ $index }}]" class="product-thumbnail-unavailable">{{ $index + 1 }}<span class="sr-only">: imagem indisponível</span></span>
                                </button>
                            @endforeach
                        </div>
                        <p class="product-gallery__status" aria-live="polite" x-text="'Imagem ' + (selected + 1) + ' de {{ count($gallery) }}'">Imagem 1 de {{ count($gallery) }}</p>
                    @endif
                </section>
                <section class="product-summary" aria-labelledby="product-title">
                    <div class="product-brand-row">
                        @if($product->brand !== '')<p class="product-brand">{{ $product->brand }}</p>@endif
                        <x-favorite-button :product="$product" icon="heart-square" class="favorite-control--product" />
                    </div>
                    <h1 id="product-title">{{ $product->name }}</h1>
                    <p class="product-meta">{{ $product->category->name }} <span aria-hidden="true">·</span> SKU {{ $product->sku }}</p>
                    @if($product->rating_count > 0)
                        <p class="product-rating"><span aria-hidden="true">★</span><span class="sr-only">Avaliação </span>{{ number_format((float) $product->rating_average, 1, ',', '.') }}<span class="sr-only"> de 5</span> <span>({{ $product->rating_count }} {{ $product->rating_count === 1 ? 'avaliação' : 'avaliações' }})</span></p>
                    @else
                        <p class="product-rating product-meta">Sem avaliações</p>
                    @endif
                    <div class="product-pricing">
                        <p class="product-price">{{ \App\Domain\Catalog\Price::fromCents($product->price_cents)->brl() }}</p>
                        <p class="product-payment">Pagamento simulado no ambiente de demonstração. Sem cobrança real.</p>
                    </div>
                    <livewire:product-add-to-cart :product="$product" />
                    <div class="product-trust">
                        <div><x-icon name="truck" /><p><strong>Entrega e retirada</strong><span>Consulte modalidades, prazo e valor no checkout.</span></p></div>
                        <div><x-icon name="lock-closed" /><p><strong>Compra com sua conta</strong><span>Entre para adicionar produtos e acompanhar seus pedidos.</span></p></div>
                    </div>
                </section>
            </article>
            <div class="product-information ds-panel">
                <section aria-labelledby="product-description-title">
                    <h2 id="product-description-title">Descrição</h2>
                    <p class="product-description">{{ trim($product->description ?? '') !== '' ? $product->description : 'Descrição não informada.' }}</p>
                </section>
                <section aria-labelledby="product-specifications-title">
                    <h2 id="product-specifications-title">Especificações</h2>
                    <dl class="product-specifications">
                        @if($product->brand !== '')<div><dt>Marca</dt><dd>{{ $product->brand }}</dd></div>@endif
                        <div><dt>Categoria</dt><dd>{{ $product->category->name }}</dd></div>
                        <div><dt>SKU</dt><dd>{{ $product->sku }}</dd></div>
                        @if($product->weight_grams > 0)<div><dt>Peso</dt><dd>{{ $product->weight_grams }} g</dd></div>@endif
                        @if($product->width_mm > 0 && $product->height_mm > 0 && $product->length_mm > 0)<div><dt>Dimensões (largura × altura × comprimento)</dt><dd>{{ $product->width_mm }} × {{ $product->height_mm }} × {{ $product->length_mm }} mm</dd></div>@endif
                    </dl>
                </section>
            </div>
            @if($relatedProducts->isNotEmpty())
                <section class="product-related" aria-labelledby="product-related-title">
                    <div class="product-related__heading"><h2 id="product-related-title">Produtos relacionados</h2><a href="{{ route('catalog.index', ['category' => $product->category->slug]) }}">Ver todos <span aria-hidden="true">→</span></a></div>
                    <div class="product-related__grid">@foreach($relatedProducts as $related)<x-home-product-card :product="$related" />@endforeach</div>
                </section>
            @endif
        </div>
    </main>
</x-storefront-layout>
