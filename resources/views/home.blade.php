@php
    $categoryNames = [
        'eletronicos' => 'Eletrônicos', 'informatica' => 'Informática',
        'casa-decoracao' => 'Casa e Decoração', 'moda' => 'Moda', 'esportes' => 'Esportes',
        'beleza' => 'Beleza', 'automotivo' => 'Automotivo', 'brinquedos' => 'Brinquedos', 'livros' => 'Livros',
    ];
    $categoryImages = [
        'eletronicos' => 'eletronicos.png', 'informatica' => 'informatica.png',
        'casa-decoracao' => 'casa-decoracao.png', 'moda' => 'moda.png', 'esportes' => 'esportes.png',
        'beleza' => 'beleza.png', 'automotivo' => 'automotivo.png', 'brinquedos' => 'brinquedos.png', 'livros' => 'livros.png',
    ];
    $categoryIcons = [
        'eletronicos' => 'device-phone-mobile', 'informatica' => 'computer-desktop',
        'casa-decoracao' => 'home', 'moda' => 'shirt', 'esportes' => 'trophy',
        'beleza' => 'sparkles', 'automotivo' => 'truck', 'brinquedos' => 'sparkles', 'livros' => 'book-open',
    ];
@endphp

<x-storefront-layout>
    <main id="storefront-content" tabindex="-1" class="home-page">
        <div class="ds-storefront-container home-content">
            <section class="home-hero" aria-labelledby="home-title">
                <div class="home-hero__copy">
                    <span class="home-hero__tag"><x-icon name="tag" />Ofertas incríveis para você</span>
                    <h1 id="home-title">Tudo o que você precisa<br>em <span class="home-hero__highlight">um só lugar.</span></h1>
                    <p>Produtos incríveis para o seu dia a dia<br class="hidden sm:block"> com entrega em todo o Brasil.</p>
                    <a href="#ofertas" class="ds-button ds-button--secondary home-hero__cta">Ver ofertas <span class="home-hero__arrow" aria-hidden="true"><span>→</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16m-7-7 7 7-7 7" /></svg></span></a>
                </div>
                <div class="home-hero__art">
                    <img src="{{ asset('images/hero-headphones.png') }}" alt="Headphone fictício em cenário navy e azul" width="1672" height="941" fetchpriority="high">
                </div>
            </section>

            <section class="home-section" aria-labelledby="categories-title">
                <div class="home-section__heading">
                    <h2 id="categories-title">Categorias</h2>
                </div>
                <nav class="home-shortcuts" aria-label="Atalhos de categorias">
                    @foreach ($categoryNames as $slug => $name)
                        <a href="{{ route('catalog.index', ['category' => $slug]) }}">
                            <span class="home-shortcuts__circle home-shortcuts__circle--{{ $slug }}" aria-hidden="true">
                                @if (isset($categoryImages[$slug]) && is_file(public_path('images/categories/'.$categoryImages[$slug])))
                                    <img src="{{ asset('images/categories/'.$categoryImages[$slug]) }}" alt="" width="480" height="480" decoding="async">
                                @else
                                    <x-icon :name="$categoryIcons[$slug] ?? 'shopping-bag'" />
                                @endif
                            </span>
                            <span>{{ $name }}</span>
                        </a>
                    @endforeach
                    <a href="{{ route('catalog.index') }}">
                        <span class="home-shortcuts__circle home-shortcuts__circle--mais" aria-hidden="true">
                            @if (is_file(public_path('images/categories/mais.png')))
                                <img src="{{ asset('images/categories/mais.png') }}" alt="" width="480" height="480" decoding="async">
                            @else
                                <x-icon name="ellipsis-horizontal" />
                            @endif
                        </span>
                        <span>Mais</span>
                    </a>
                </nav>
            </section>

            <section id="ofertas" class="home-section" aria-labelledby="offers-title">
                <div class="home-section__heading">
                    <h2 id="offers-title">Ofertas em destaque</h2>
                    <a href="{{ route('catalog.index') }}" class="home-section__action">Ver todas <span aria-hidden="true">→</span></a>
                </div>
                <div class="home-products">
                    @forelse ($products as $item)
                        <x-home-product-card :product="$item" :quick-actions="true" cart-context="offers" />
                    @empty
                        <p class="ds-card home-empty">Os produtos aparecerão aqui quando estiverem disponíveis.</p>
                    @endforelse
                </div>
            </section>

            <section class="home-benefits" aria-label="Informações de compra">
                <div class="home-benefit home-benefit--blue">
                    <x-icon name="truck" />
                    <div><h2>Opções de entrega</h2><p>Consulte modalidades e valores no checkout.</p></div>
                </div>
                <div class="home-benefit home-benefit--green">
                    <x-icon name="credit-card" />
                    <div><h2>Pagamento de demonstração</h2><p>Ambiente fictício, sem cobrança real.</p></div>
                </div>
                <div class="home-benefit home-benefit--orange">
                    <x-icon name="lock-closed" />
                    <div><h2>Compra com segurança</h2><p>Acesse sua conta para acompanhar seus pedidos.</p></div>
                </div>
            </section>

            <section class="home-section" aria-labelledby="bestsellers-title">
                <div class="home-section__heading">
                    <h2 id="bestsellers-title">Mais vendidos</h2>
                    <a href="{{ route('catalog.index') }}" class="home-section__action">Ver todas <span aria-hidden="true">→</span></a>
                </div>
                <div class="home-products">
                    @forelse ($bestSellers as $item)
                        <x-home-product-card :product="$item" :quick-actions="true" cart-context="bestsellers" />
                    @empty
                        <p class="ds-card home-empty">Explore o catálogo para conhecer os produtos disponíveis.</p>
                    @endforelse
                </div>
            </section>

            <section class="home-responsive" aria-labelledby="responsive-title">
                <div class="home-responsive__art">
                    <img src="{{ asset('images/home/responsive-shop.svg') }}" alt="Ilustração da vitrine ShopPlus em um computador e um celular" width="640" height="480" loading="lazy">
                </div>
                <div class="home-responsive__copy">
                    <span class="home-eyebrow">No celular, no tablet ou no computador</span>
                    <h2 id="responsive-title">ShopPlus em qualquer tela</h2>
                    <p>Explore produtos e acompanhe seus pedidos pelo navegador, onde você estiver.</p>
                    <a href="{{ route('catalog.index') }}" class="ds-button ds-button--primary">Explorar o catálogo <span aria-hidden="true">→</span></a>
                </div>
            </section>

            <section class="home-newsletter" aria-labelledby="newsletter-title">
                <x-icon name="envelope" />
                <div class="home-newsletter__copy">
                    <h2 id="newsletter-title">Novidades por e-mail</h2>
                    <p>Inscrições indisponíveis. Este canal ainda não está ativo.</p>
                </div>
                <div class="home-newsletter__preview">
                    <x-label for="home-newsletter-email" class="sr-only" value="E-mail — inscrições indisponíveis" />
                    <x-input id="home-newsletter-email" type="email" placeholder="Seu e-mail" :disabled="true" aria-describedby="newsletter-unavailable" />
                    <x-button type="button" disabled>Cadastrar</x-button>
                    <p id="newsletter-unavailable" class="home-newsletter__status">Sem coleta ou envio de e-mails nesta versão.</p>
                </div>
            </section>
        </div>
    </main>
</x-storefront-layout>
