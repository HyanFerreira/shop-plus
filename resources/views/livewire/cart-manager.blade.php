<div class="cart-manager" x-data="cartQuantityUpdates" x-bind:aria-busy="busy.toString()">
    @if(session('cart-status'))<x-alert variant="success" class="cart-flash">{{ session('cart-status') }}</x-alert>@endif
    <p x-cloak x-show="message" x-text="message" class="cart-client-error" role="alert"></p>
    <div id="cart-errors" class="cart-errors" role="alert"><x-input-error for="quantity" /><x-input-error for="cart" /></div>
    @if(!$cart || $cart->items->isEmpty())
        <section class="cart-empty ds-panel" aria-labelledby="cart-empty-title">
            <div class="cart-empty__art" aria-hidden="true"><x-icon name="shopping-cart" /></div>
            <h2 id="cart-empty-title">Seu carrinho está vazio.</h2>
            <p>Encontre seu próximo produto e adicione ao carrinho para continuar sua compra.</p>
            <a href="{{ route('catalog.index') }}" class="ds-button ds-button--primary">Explorar catálogo <x-arrow /></a>
        </section>
    @else
        <div class="cart-layout">
            <section class="cart-items ds-panel" aria-labelledby="cart-items-title">
                <div class="cart-items__heading">
                    <div class="cart-items__title"><h2 id="cart-items-title">Produtos selecionados</h2><x-badge variant="info">{{ $cart->items->count() }} {{ $cart->items->count() === 1 ? 'item' : 'itens' }}</x-badge></div>
                    <x-button type="button" variant="text" @pointerdown.prevent="void 0" @click="clearCart()" x-bind:disabled="busy" wire:loading.attr="disabled" wire:target="updateItem,removeItem,clearCart"><x-icon name="trash" class="size-5" />Limpar carrinho</x-button>
                </div>
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
                    <article class="cart-item" wire:key="cart-item-{{ $item->id }}" data-cart-item="{{ $item->id }}" aria-labelledby="cart-item-title-{{ $item->id }}">
                        <a href="{{ route('catalog.show', $product->slug) }}" class="cart-item__image" aria-label="Ver {{ $product->name }}" x-data="{ imageFailed: false }">
                            @if($imageUrl)<img src="{{ $imageUrl }}" alt="{{ $imageAlt }}" width="160" height="160" decoding="async" x-show="!imageFailed" x-on:error="imageFailed = true" x-init="if ($el.complete && $el.naturalWidth === 0) imageFailed = true">@endif
                            <span class="cart-item__fallback" @if($imageUrl) x-cloak x-show="imageFailed" @endif><x-icon name="shopping-bag" /><span>Imagem indisponível</span></span>
                        </a>
                        <div class="cart-item__body">
                            <div class="cart-item__information">
                                @if($product->brand !== '')<x-badge variant="info" class="cart-item__brand">{{ $product->brand }}</x-badge>@endif
                                <h3 id="cart-item-title-{{ $item->id }}"><a href="{{ route('catalog.show', $product->slug) }}">{{ $product->name }}</a></h3>
                                <p class="cart-item__metadata">{{ $product->category?->name }} · SKU {{ $product->sku }}</p>
                                <p class="cart-item__availability"><x-badge :variant="$available > 0 ? 'success' : 'warning'"><x-icon :name="$available > 0 ? 'check' : 'shopping-bag'" class="size-4" />{{ $available > 0 ? 'Em estoque · '.$available.' '.($available === 1 ? 'unidade' : 'unidades') : 'Sem estoque disponível' }}</x-badge></p>
                            </div>
                            <div class="cart-item__commercial">
                                <x-button variant="danger" size="icon" type="button" class="cart-remove" @pointerdown.prevent="void 0" @click="removeItem({{ $item->id }})" x-bind:disabled="busy" wire:loading.attr="disabled" wire:target="updateItem,removeItem,clearCart" aria-label="Remover {{ $product->name }}" title="Remover {{ $product->name }}"><x-icon name="trash" class="size-5" /></x-button>
                                <div class="cart-item__price"><span>Preço unitário</span><strong>{{ \App\Domain\Catalog\Price::fromCents($product->price_cents)->brl() }}</strong>@if($item->quantity > 1)<span>Subtotal: {{ \App\Domain\Catalog\Price::fromCents($product->price_cents * $item->quantity)->brl() }}</span>@endif</div>
                            </div>
                        </div>
                        <div class="cart-item__controls">
                            <div class="cart-quantity-field">
                                <x-label for="cart-quantity-{{ $item->id }}" value="Quantidade" />
                                <x-quantity-control class="cart-quantity">
                                    <x-slot:decrement>
                                        <x-button type="button" variant="secondary" size="lg" aria-label="Diminuir quantidade de {{ $product->name }}" wire:loading.attr="disabled" wire:target="updateItem,removeItem,clearCart" :disabled="$available === 0 || ($quantities[$item->id] ?? 0) <= 1" x-bind:disabled="busy || Number($wire.quantities[{{ $item->id }}]) <= 1 || {{ $available === 0 ? 'true' : 'false' }}" @pointerdown.prevent="void 0" @click="changeQuantity({{ $item->id }}, -1, {{ $limit }})">−</x-button>
                                    </x-slot:decrement>
                                    <x-input id="cart-quantity-{{ $item->id }}" aria-label="Quantidade de {{ $product->name }}" type="number" inputmode="numeric" min="0" step="1" max="{{ max(1, $limit) }}" wire:model="quantities.{{ $item->id }}" @input="stageQuantity({{ $item->id }}, $event.target.value)" @change="saveQuantity({{ $item->id }}, $event.target.value)" @keydown.enter.prevent="$el.blur()" x-bind:disabled="busy" wire:loading.attr="disabled" wire:target="updateItem,removeItem,clearCart" aria-describedby="cart-item-error-{{ $item->id }} cart-errors" aria-invalid="{{ $errors->has('quantities.'.$item->id) || $errors->has('quantity') ? 'true' : 'false' }}" />
                                    <x-slot:increment>
                                        <x-button type="button" variant="secondary" size="lg" aria-label="Aumentar quantidade de {{ $product->name }}" wire:loading.attr="disabled" wire:target="updateItem,removeItem,clearCart" :disabled="($quantities[$item->id] ?? 0) >= $limit" x-bind:disabled="busy || Number($wire.quantities[{{ $item->id }}]) >= {{ $limit }}" @pointerdown.prevent="void 0" @click="changeQuantity({{ $item->id }}, 1, {{ $limit }})">+</x-button>
                                    </x-slot:increment>
                                </x-quantity-control>
                            </div>
                            <p class="cart-item__saved">{{ $item->quantity }} {{ $item->quantity === 1 ? 'unidade no carrinho' : 'unidades no carrinho' }}</p>
                        </div>
                        <div id="cart-item-error-{{ $item->id }}" class="cart-item__error" aria-live="polite"><x-input-error for="{{ 'quantities.'.$item->id }}" /></div>
                        @if($item->quantity > $available)<p class="cart-item__stock">O estoque disponível está abaixo da quantidade no carrinho. Revise a quantidade antes de continuar.</p>@endif
                    </article>
                @endforeach
            </section>
            <aside class="cart-summary ds-panel" aria-labelledby="cart-summary-title">
                <div class="cart-summary__heading"><span class="cart-summary__icon"><x-icon name="shopping-bag" class="size-6" /></span><h2 id="cart-summary-title">Resumo do pedido</h2></div>
                <dl class="cart-summary__details">
                    <div><dt>Subtotal dos produtos</dt><dd data-cart-subtotal>{{ \App\Domain\Catalog\Price::fromCents($subtotal)->brl() }}</dd></div>
                    <div class="cart-summary__shipping"><dt><x-icon name="truck" class="size-4" />Entrega</dt><dd>A definir no checkout</dd></div>
                    <div class="cart-summary__total"><dt>Total dos produtos</dt><dd>{{ \App\Domain\Catalog\Price::fromCents($subtotal)->brl() }}</dd></div>
                </dl>
                <p class="cart-summary__help">O total final depende da modalidade de entrega escolhida no checkout.</p>
                <a id="cart-checkout" @pointerdown.prevent="void 0" @click.prevent="navigate($event.currentTarget.href)" x-bind:aria-disabled="busy.toString()" href="{{ route('checkout.show') }}" class="ds-button ds-button--primary"><x-icon name="lock-closed" class="size-4" />Ir para checkout <x-arrow /></a>
                <a href="{{ route('catalog.index') }}" class="cart-continue ds-button ds-button--secondary" @pointerdown.prevent="void 0" @click.prevent="navigate($event.currentTarget.href)" x-bind:aria-disabled="busy.toString()"><x-icon name="shopping-cart" class="size-5" />Continuar comprando</a>
                <p class="cart-update-status" role="status"><x-icon name="shield-check" class="size-5" /><span x-show="busy">Atualizando carrinho…</span><span x-show="!busy">Valores baseados nos itens salvos.</span></p>
            </aside>
        </div>
    @endif
</div>
