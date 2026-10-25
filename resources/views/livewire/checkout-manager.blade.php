<div class="checkout-manager">
    <div class="checkout-global-errors" role="alert"><x-input-error for="checkout" /><x-input-error for="shipping" /><x-input-error for="cart" /><x-input-error for="quantity" /><x-input-error for="checkoutKey" /></div>
    @if(!$cart || $cart->items->isEmpty())
        <section class="checkout-empty ds-panel" aria-labelledby="checkout-empty-title"><x-icon name="shopping-cart" /><h2 id="checkout-empty-title">Seu carrinho está vazio.</h2><p>Explore o catálogo e adicione produtos antes de criar um pedido.</p><a class="ds-button ds-button--primary" href="{{ route('catalog.index') }}">Explorar catálogo</a></section>
    @else
        <div class="checkout-layout">
            <div class="checkout-main">
                <section class="checkout-panel ds-panel" aria-labelledby="checkout-items-title">
                    <div class="checkout-section-heading"><span aria-hidden="true">1</span><h2 id="checkout-items-title">Produtos selecionados</h2></div>
                    <ul class="checkout-items">
                        @foreach($cart->items as $item)
                            @php
                                $product = $item->product;
                                $disk = Storage::disk('public');
                                $photo = $product->images->first(fn ($image) => $disk->exists($image->path));
                                $demo = config('demo-images.products')[$product->sku] ?? null;
                                $imageUrl = $photo ? $disk->url($photo->path) : ($demo ? asset('images/demo-products/'.$demo['file']) : null);
                                $imageAlt = $photo ? ($photo->alt_text ?: $product->name) : ($demo['alt'] ?? '');
                            @endphp
                            <li class="checkout-item" wire:key="checkout-item-{{ $item->id }}">
                                <div class="checkout-item__image" x-data="{ imageFailed: false }">
                                    @if($imageUrl)<img src="{{ $imageUrl }}" alt="{{ $imageAlt }}" width="160" height="160" decoding="async" x-show="!imageFailed" x-on:error="imageFailed = true" x-init="if ($el.complete && $el.naturalWidth === 0) imageFailed = true">@endif
                                    <span class="checkout-item__fallback" @if($imageUrl) x-cloak x-show="imageFailed" @endif><x-icon name="shopping-bag" /><span>Imagem indisponível</span></span>
                                </div>
                                <div class="checkout-item__copy"><h3>{{ $product->name }}</h3><p>Quantidade: {{ $item->quantity }}</p><strong>{{ \App\Domain\Catalog\Price::fromCents($lineTotals[$item->id])->brl() }}</strong></div>
                            </li>
                        @endforeach
                    </ul>
                    <a class="checkout-text-link" href="{{ route('cart.show') }}">Revisar carrinho <span aria-hidden="true">→</span></a>
                </section>
                <section class="checkout-panel ds-panel" aria-labelledby="checkout-address-title">
                    <div class="checkout-section-heading"><span aria-hidden="true">2</span><h2 id="checkout-address-title">Endereço de entrega</h2></div>
                    @if(Auth::user()->addresses->isEmpty())
                        <div class="checkout-address-empty"><p>Cadastre um endereço para continuar</p><span>O endereço é necessário para consultar as modalidades de entrega.</span><a class="checkout-text-link" href="{{ route('personal-data.show') }}">Cadastrar endereço <span aria-hidden="true">→</span></a></div>
                    @else
                        @php
                            $addressLabelCounts = Auth::user()->addresses->countBy('label');
                            $hasDuplicateAddressLabels = $addressLabelCounts->contains(fn ($count) => $count > 1);
                        @endphp
                        <div class="checkout-address-select">
                            <x-label for="address" value="Selecione um endereço cadastrado" />
                            <select id="address" wire:model.live="addressId" class="ds-input" wire:loading.attr="disabled" wire:target="confirm" aria-describedby="checkout-address-help checkout-address-error" aria-invalid="{{ $errors->has('addressId') ? 'true' : 'false' }}">
                                <option value="">Selecione</option>
                                @foreach(Auth::user()->addresses as $address)<option value="{{ $address->id }}">{{ $address->label }}{{ $addressLabelCounts[$address->label] > 1 ? ' · cadastro '.$loop->iteration : '' }}</option>@endforeach
                            </select>
                            <p id="checkout-address-help" class="ds-help">As modalidades são consultadas para o endereço selecionado.</p>
                            @if($hasDuplicateAddressLabels)<p class="checkout-address-duplicate" role="alert">Há endereços com o mesmo nome. Antes de continuar, revise e diferencie os nomes no cadastro.</p>@endif
                            <a class="checkout-text-link" href="{{ route('personal-data.show') }}">Gerenciar endereços <span aria-hidden="true">→</span></a>
                        </div>
                    @endif
                    <div id="checkout-address-error" aria-live="polite"><x-input-error for="addressId" /></div>
                </section>
                <section class="checkout-panel ds-panel" aria-labelledby="checkout-shipping-title">
                    <div class="checkout-section-heading"><span aria-hidden="true">3</span><h2 id="checkout-shipping-title">Modalidade de entrega</h2></div>
                    @if($options)
                        <fieldset class="checkout-shipping-options" aria-describedby="checkout-shipping-error">
                            <legend class="sr-only">Escolha a modalidade de entrega</legend>
                            @foreach($options as $key => $option)
                                <label class="checkout-shipping-choice" wire:key="shipping-{{ $key }}">
                                    <input type="radio" name="checkout-shipping" wire:model.live="shippingMethod" value="{{ $key }}" wire:loading.attr="disabled" wire:target="confirm">
                                    <span><strong>{{ $option['label'] }}</strong><span>Até {{ $option['days'] }} {{ $option['days'] === 1 ? 'dia' : 'dias' }}</span></span>
                                    <strong>{{ \App\Domain\Catalog\Price::fromCents($option['price_cents'])->brl() }}</strong>
                                </label>
                            @endforeach
                        </fieldset>
                    @else
                        <p class="checkout-panel-help">Selecione um endereço para consultar prazo e valor de entrega.</p>
                    @endif
                    <div id="checkout-shipping-error" aria-live="polite"><x-input-error for="shippingMethod" /></div>
                </section>
            </div>
            <aside class="checkout-summary checkout-panel ds-panel" aria-labelledby="checkout-summary-title">
                <h2 id="checkout-summary-title">Resumo do pedido</h2>
                <dl class="checkout-summary__details">
                    <div><dt>Subtotal dos produtos</dt><dd data-checkout-subtotal>{{ \App\Domain\Catalog\Price::fromCents($subtotal)->brl() }}</dd></div>
                    <div><dt>Entrega{{ $selectedOption ? ' · '.$selectedOption['label'] : '' }}</dt><dd data-checkout-shipping>{{ $selectedOption ? \App\Domain\Catalog\Price::fromCents($selectedOption['price_cents'])->brl() : 'Selecione um endereço' }}</dd></div>
                    <div class="checkout-summary__total"><dt>Total estimado</dt><dd data-checkout-total>{{ $estimatedTotal !== null ? \App\Domain\Catalog\Price::fromCents($estimatedTotal)->brl() : 'A definir' }}</dd></div>
                </dl>
                <p class="ds-help">Preços, entrega e estoque são conferidos novamente ao criar o pedido.</p>
                @if($options)
                    <x-button id="checkout-confirm" type="button" class="checkout-primary" wire:click="confirm" wire:loading.attr="disabled" wire:target="addressId,shippingMethod,confirm">
                        <span wire:loading.remove wire:target="confirm">Criar pedido e reservar estoque</span><span wire:loading wire:target="confirm">Criando pedido…</span>
                    </x-button>
                @endif
                <p class="checkout-next-step"><x-icon name="lock-closed" /><span>O pagamento fictício será feito na próxima tela. Nenhum dado de cartão é solicitado aqui.</span></p>
                <p class="checkout-loading" role="status"><span wire:loading wire:target="addressId,shippingMethod">Atualizando entrega e estimativa…</span><span wire:loading wire:target="confirm">Criando seu pedido e reservando estoque…</span></p>
            </aside>
        </div>
    @endif
</div>
