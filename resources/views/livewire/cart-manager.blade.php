<div class="cart-manager">
    @if(session('cart-status'))<x-alert variant="success" class="cart-flash">{{ session('cart-status') }}</x-alert>@endif
    <div id="cart-errors" class="cart-errors" role="alert">
        <x-input-error for="quantity" /><x-input-error for="cart" />
    </div>
    @if(!$cart || $cart->items->isEmpty())
        <section class="cart-empty ds-panel" aria-labelledby="cart-empty-title">
            <div class="cart-empty__art" aria-hidden="true"><x-icon name="shopping-cart" /></div>
            <h2 id="cart-empty-title">Seu carrinho está vazio.</h2>
            <p>Encontre seu próximo produto e adicione ao carrinho para continuar sua compra.</p>
            <a href="{{ route('catalog.index') }}" class="ds-button ds-button--primary">Explorar catálogo <span aria-hidden="true">→</span></a>
        </section>
    @else
        <div class="cart-layout">
            <section class="cart-items" aria-labelledby="cart-items-title">
                <div class="cart-items__heading">
                    <h2 id="cart-items-title">Produtos selecionados</h2>
                    <p>{{ $cart->items->count() }} {{ $cart->items->count() === 1 ? 'produto' : 'produtos' }}</p>
                </div>
                <p class="cart-instructions">+ e − atualizam automaticamente. Para digitar uma quantidade, confirme em Atualizar.</p>
                @foreach($cart->items as $item)
                    @php
                        $product = $item->product;
                        $available = max(0, ($product->inventoryItem?->on_hand ?? 0) - ($product->inventoryItem?->reserved ?? 0));
                        $limit = min(99, $available);
                        $disk = Storage::disk('public');
                        $photo = $product->images->first(fn ($image) => $disk->exists($image->path));
                        $demo = config('demo-images.products')[$product->sku] ?? null;
                        $imageUrl = $photo ? $disk->url($photo->path) : ($demo ? asset('images/demo-products/'.$demo['file']) : null);
                        $imageAlt = $photo ? ($photo->alt_text ?: $product->name) : ($demo['alt'] ?? '');
                    @endphp
                    <article class="cart-item ds-panel" wire:key="cart-item-{{ $item->id }}" data-cart-item="{{ $item->id }}" aria-labelledby="cart-item-title-{{ $item->id }}" x-data="{}">
                        <a href="{{ route('catalog.show', $product->slug) }}" class="cart-item__image" aria-label="Ver {{ $product->name }}" x-data="{ imageFailed: false }">
                            @if($imageUrl)
                                <img src="{{ $imageUrl }}" alt="{{ $imageAlt }}" width="160" height="160" decoding="async" x-show="!imageFailed" x-on:error="imageFailed = true" x-init="if ($el.complete && $el.naturalWidth === 0) imageFailed = true">
                            @endif
                            <span class="cart-item__fallback" @if($imageUrl) x-cloak x-show="imageFailed" @endif><x-icon name="shopping-bag" /><span>Imagem indisponível</span></span>
                        </a>
                        <div class="cart-item__body">
                            <div class="cart-item__information">
                                @if($product->brand !== '')<p class="cart-item__brand">{{ $product->brand }}</p>@endif
                                <h3 id="cart-item-title-{{ $item->id }}"><a href="{{ route('catalog.show', $product->slug) }}">{{ $product->name }}</a></h3>
                                <p class="cart-item__saved">{{ $item->quantity }} {{ $item->quantity === 1 ? 'unidade no carrinho' : 'unidades no carrinho' }}</p>
                            </div>
                            <div class="cart-item__price"><span>Preço unitário</span><strong>{{ \App\Domain\Catalog\Price::fromCents($product->price_cents)->brl() }}</strong></div>
                        </div>
                        <div class="cart-item__controls">
                            <div class="cart-quantity-field">
                                <x-label for="cart-quantity-{{ $item->id }}" value="Quantidade" />
                                <div class="cart-quantity">
                                    <x-button type="button" variant="secondary" aria-label="Diminuir quantidade de {{ $product->name }}" wire:loading.attr="disabled" wire:target="updateItem,removeItem"
                                        :disabled="$available === 0 || ($quantities[$item->id] ?? 0) <= 1"
                                        x-bind:disabled="Number($wire.quantities[{{ $item->id }}]) <= 1 || {{ $available === 0 ? 'true' : 'false' }}"
                                        @click="$wire.set('quantities.{{ $item->id }}', Math.max(1, Number($wire.quantities[{{ $item->id }}]) - 1), false); $wire.updateItem({{ $item->id }})">−</x-button>
                                    <x-input id="cart-quantity-{{ $item->id }}" aria-label="Quantidade de {{ $product->name }}" type="number" inputmode="numeric" min="0" max="{{ max(1, $limit) }}" wire:model="quantities.{{ $item->id }}" wire:loading.attr="disabled" wire:target="updateItem,removeItem" aria-describedby="cart-item-error-{{ $item->id }} cart-errors" aria-invalid="{{ $errors->has('quantities.'.$item->id) || $errors->has('quantity') ? 'true' : 'false' }}" />
                                    <x-button type="button" variant="secondary" aria-label="Aumentar quantidade de {{ $product->name }}" wire:loading.attr="disabled" wire:target="updateItem,removeItem"
                                        :disabled="($quantities[$item->id] ?? 0) >= $limit"
                                        x-bind:disabled="Number($wire.quantities[{{ $item->id }}]) >= {{ $limit }}"
                                        @click="$wire.set('quantities.{{ $item->id }}', Math.min({{ $limit }}, Number($wire.quantities[{{ $item->id }}]) + 1), false); $wire.updateItem({{ $item->id }})">+</x-button>
                                </div>
                            </div>
                            <div class="cart-item__actions">
                                <x-button variant="secondary" type="button" wire:click="updateItem({{ $item->id }})" wire:loading.attr="disabled" wire:target="updateItem,removeItem">Atualizar</x-button>
                                <x-button variant="secondary" type="button" class="cart-remove" wire:click="removeItem({{ $item->id }})" wire:loading.attr="disabled" wire:target="updateItem,removeItem" aria-label="Remover {{ $product->name }}"><x-icon name="trash" />Remover</x-button>
                            </div>
                        </div>
                        <div id="cart-item-error-{{ $item->id }}" class="cart-item__error" aria-live="polite"><x-input-error for="{{ 'quantities.'.$item->id }}" /></div>
                        @if($item->quantity > $available)
                            <p class="cart-item__stock">O estoque disponível está abaixo da quantidade no carrinho. Revise a quantidade antes de continuar.</p>
                        @endif
                    </article>
                @endforeach
            </section>
            <aside class="cart-summary ds-panel" aria-labelledby="cart-summary-title">
                <h2 id="cart-summary-title">Resumo do pedido</h2>
                <dl class="cart-summary__details">
                    <div><dt>Subtotal dos produtos</dt><dd data-cart-subtotal>{{ \App\Domain\Catalog\Price::fromCents($subtotal)->brl() }}</dd></div>
                    <div class="cart-summary__shipping"><dt>Entrega</dt><dd>A definir no checkout</dd></div>
                    <div class="cart-summary__total"><dt>Total dos produtos</dt><dd>{{ \App\Domain\Catalog\Price::fromCents($subtotal)->brl() }}</dd></div>
                </dl>
                <p class="cart-summary__help">O total final depende da modalidade de entrega escolhida no checkout.</p>
                <a id="cart-checkout" href="{{ route('checkout.show') }}" class="ds-button ds-button--primary">Ir para checkout <span aria-hidden="true">→</span></a>
                <a href="{{ route('catalog.index') }}" class="cart-continue">Continuar comprando</a>
                <p class="cart-update-status" role="status"><span wire:loading wire:target="updateItem,removeItem">Atualizando carrinho…</span><span wire:loading.remove wire:target="updateItem,removeItem">Valores baseados nos itens salvos.</span></p>
            </aside>
        </div>
    @endif
</div>
