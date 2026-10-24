@php
    $shortcutCategories = [
        ['name' => 'Eletrônicos', 'icon' => 'device-phone-mobile', 'image' => 'phone.svg'],
        ['name' => 'Informática', 'icon' => 'computer-desktop', 'image' => 'notebook.svg'],
        ['name' => 'Casa e Decoração', 'icon' => 'home', 'image' => 'lamp.svg'],
        ['name' => 'Moda', 'icon' => 'shirt', 'image' => 'sneaker.svg'],
        ['name' => 'Esportes', 'icon' => 'trophy', 'image' => 'dumbbells.svg'],
        ['name' => 'Beleza', 'icon' => 'sparkles', 'image' => 'perfume.svg'],
        ['name' => 'Automotivo', 'icon' => 'truck'],
        ['name' => 'Brinquedos', 'icon' => 'sparkles'],
        ['name' => 'Livros', 'icon' => 'book-open'],
        ['name' => 'Mais', 'icon' => 'ellipsis-horizontal'],
    ];
@endphp

<x-storefront-layout>
    <main id="storefront-content" tabindex="-1" class="home-page">
        <div class="ds-storefront-container home-content">
            <section class="home-hero" aria-labelledby="home-title">
                <div class="home-hero__copy">
                    <h1 id="home-title">Tecnologia com<br>as melhores ofertas</h1>
                    <p>Produtos incríveis para o seu dia a dia<br class="hidden sm:block"> com entrega em todo o Brasil.</p>
                    <a href="#ofertas" class="ds-button ds-button--secondary home-hero__cta">Ver ofertas <span aria-hidden="true">→</span></a>
                </div>
                <div class="home-hero__art">
                    <img src="{{ asset('images/hero-headphones.png') }}" alt="Headphone fictício em cenário navy e azul" width="1672" height="941" fetchpriority="high">
                </div>
            </section>

            <nav class="home-shortcuts" aria-label="Atalhos de categorias">
                @foreach ($shortcutCategories as $shortcut)
                    <a href="{{ route('catalog.index') }}">
                        <span class="home-shortcuts__circle"><x-icon :name="$shortcut['icon']" /></span>
                        <span>{{ $shortcut['name'] }}</span>
                    </a>
                @endforeach
            </nav>

            <section id="ofertas" class="home-section" aria-labelledby="offers-title">
                <div class="home-section__heading">
                    <h2 id="offers-title">Ofertas em destaque</h2>
                    <a href="{{ route('catalog.index') }}" class="home-section__action">Ver todas <span aria-hidden="true">→</span></a>
                </div>
                <div class="home-products">
                    @forelse ($products as $item)
                        <x-home-product-card :product="$item" />
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
                        <x-home-product-card :product="$item" />
                    @empty
                        <p class="ds-card home-empty">Explore o catálogo para conhecer os produtos disponíveis.</p>
                    @endforelse
                </div>
            </section>

            <section class="home-section" aria-labelledby="categories-title">
                <div class="home-section__heading">
                    <h2 id="categories-title">Categorias em destaque</h2>
                    <a href="{{ route('catalog.index') }}" class="home-section__action">Ver todas <span aria-hidden="true">→</span></a>
                </div>
                <div class="home-categories">
                    @foreach (array_slice($shortcutCategories, 0, 6) as $category)
                        <a href="{{ route('catalog.index') }}" class="home-category">
                            <div class="home-category__art"><img src="{{ asset('images/demo-products/'.$category['image']) }}" alt="" width="640" height="480" loading="lazy"></div>
                            <h3>{{ $category['name'] }}</h3>
                            <span>Ver produtos <span aria-hidden="true">→</span></span>
                        </a>
                    @endforeach
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
