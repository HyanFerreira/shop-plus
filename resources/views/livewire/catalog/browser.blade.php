<div class="catalog-browser">
    @php
        $sortOptions = ['relevance' => 'Mais relevantes', 'rating' => 'Melhor avaliados', 'name' => 'Nome', 'price_asc' => 'Menor preço', 'price_desc' => 'Maior preço'];
        $activeLabels = [];
        if (trim($search) !== '') $activeLabels['search'] = 'Busca: '.$search;
        foreach (array_unique([...$selectedCategories, $category]) as $slug) {
            if ($slug !== '') $activeLabels['category-'.$slug] = optional($categories->firstWhere('slug', $slug))->name ?? $slug;
        }
        foreach ($brands as $brand) $activeLabels['brand-'.md5($brand)] = 'Marca: '.$brand;
        if (is_numeric($minPrice) && (int) $minPrice > 0) $activeLabels['min-price'] = 'A partir de R$ '.number_format((int) $minPrice, 0, ',', '.');
        if (is_numeric($maxPrice) && (int) $maxPrice > 0) $activeLabels['max-price'] = 'Até R$ '.number_format((int) $maxPrice, 0, ',', '.');
        if ($minimumRating > 0) $activeLabels['rating'] = $minimumRating.'+ estrelas';
        if ($inStock) $activeLabels['stock'] = 'Em estoque';
        if ($freeShipping) $activeLabels['free-shipping'] = 'Frete grátis';
        if ($expressShipping) $activeLabels['express-shipping'] = 'Entrega rápida';
    @endphp

    <nav class="catalog-breadcrumb" aria-label="Caminho de navegação">
        <a href="{{ route('home') }}">Início</a><span aria-hidden="true">›</span><span aria-current="page">Catálogo</span>
    </nav>
    <div class="catalog-heading">
        <h1>{{ $category !== '' ? optional($categories->firstWhere('slug', $category))->name ?? 'Catálogo' : 'Catálogo' }}</h1>
        <p>Encontre produtos fictícios para testar sua experiência de compra.</p>
    </div>

    <div class="catalog-layout">
        <aside class="catalog-sidebar" aria-label="Filtros do catálogo">
            <details id="catalog-filters" class="catalog-filters ds-panel" wire:key="catalog-filters" wire:ignore.self
                x-data="{ expanded: window.matchMedia('(min-width: 1024px)').matches }"
                x-bind:open="expanded" @toggle="expanded = $el.open"
                @resize.window.debounce.150ms="if (window.matchMedia('(min-width: 1024px)').matches) expanded = true"
                @keydown.escape.stop="if (expanded && !window.matchMedia('(min-width: 1024px)').matches) { expanded = false; $refs.filterSummary.focus() }">
                <summary id="catalog-filter-summary" x-ref="filterSummary" x-bind:aria-expanded="expanded.toString()" aria-controls="catalog-filter-body">
                    <span>Filtros @if(count($activeLabels))<x-badge variant="info">{{ count($activeLabels) }}</x-badge>@endif</span>
                    <span class="catalog-filter-toggle" x-text="expanded ? 'Ocultar' : 'Mostrar'">Mostrar</span>
                </summary>
                <div id="catalog-filter-body" class="catalog-filter-body">
                    @if(trim($search) !== '')
                        <div class="catalog-filter-actions">
                            <button type="button" wire:click="$set('search', '')" class="catalog-text-action">Limpar busca</button>
                        </div>
                    @endif
                    <div class="catalog-filter-sections">
                        <div class="catalog-filter-group catalog-filter-search">
                            <x-label for="catalogSearch" value="Buscar" />
                            <div class="catalog-search-field">
                                <x-input id="catalogSearch" wire:model.live.debounce.300ms="search" placeholder="Nome, marca..." />
                                <x-icon name="magnifying-glass" />
                            </div>
                        </div>

                        <fieldset class="catalog-filter-group">
                            <legend>Categorias</legend>
                            <div class="catalog-choices">
                                @foreach ($categories as $item)
                                    <label class="catalog-choice" wire:key="filter-category-{{ $item->id }}">
                                        <x-checkbox wire:model.live="selectedCategories" value="{{ $item->slug }}" />
                                        <span>{{ $item->name }}</span><span class="catalog-choice__count">({{ $item->products_count }})</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset class="catalog-filter-group">
                            <legend>Faixa de preço</legend>
                            <div class="catalog-price-fields">
                                <div><x-label for="catalogMinPrice" value="Mínimo" /><x-input id="catalogMinPrice" wire:model.live.debounce.500ms="minPrice" type="number" min="0" placeholder="R$ 0" /></div>
                                <div><x-label for="catalogMaxPrice" value="Máximo" /><x-input id="catalogMaxPrice" wire:model.live.debounce.500ms="maxPrice" type="number" min="0" max="{{ $priceCeiling }}" placeholder="R$ {{ number_format($priceCeiling, 0, ',', '.') }}" /></div>
                            </div>
                        </fieldset>

                        <fieldset class="catalog-filter-group">
                            <legend>Marcas</legend>
                            <div class="catalog-choices">
                                @foreach ($availableBrands as $brand)
                                    <label class="catalog-choice" wire:key="filter-brand-{{ md5($brand->brand) }}">
                                        <x-checkbox wire:model.live="brands" value="{{ $brand->brand }}" />
                                        <span>{{ $brand->brand }}</span><span class="catalog-choice__count">({{ $brand->products_count }})</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset class="catalog-filter-group">
                            <legend>Avaliação mínima</legend>
                            <div class="catalog-choices">
                                @foreach ([0 => 'Qualquer avaliação', 4 => '4 estrelas ou mais', 3 => '3 estrelas ou mais', 2 => '2 estrelas ou mais'] as $value => $label)
                                    <label class="catalog-choice">
                                        <input type="radio" wire:model.live="minimumRating" value="{{ $value }}" class="catalog-radio">
                                        <span>@if($value > 0)<span class="catalog-stars" aria-hidden="true">{{ str_repeat('★', $value) }}</span>@endif {{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset class="catalog-filter-group">
                            <legend>Disponibilidade</legend>
                            <label class="catalog-choice"><x-checkbox wire:model.live="inStock" /><span>Em estoque</span></label>
                        </fieldset>
                        <fieldset class="catalog-filter-group">
                            <legend>Entrega</legend>
                            <label class="catalog-choice"><x-checkbox wire:model.live="freeShipping" /><span>Frete grátis</span></label>
                            <label class="catalog-choice"><x-checkbox wire:model.live="expressShipping" /><span>Entrega rápida</span></label>
                        </fieldset>
                    </div>
                </div>
            </details>
        </aside>

        <section class="catalog-results" aria-labelledby="catalog-results-title">
            <h2 id="catalog-results-title" class="sr-only">Produtos encontrados</h2>
            <div class="catalog-toolbar">
                <p class="catalog-total" role="status">{{ $products->total() }} {{ $products->total() === 1 ? 'produto encontrado' : 'produtos encontrados' }}</p>
                <div class="catalog-sort">
                    <span id="catalog-sort-label">Ordenar por</span>
                    <x-catalog-dropdown id="catalog-sort" model="sort" :options="$sortOptions" :value="$sort" labelled-by="catalog-sort-label" />
                </div>
            </div>

            @if ($activeLabels !== [])
                <div id="catalog-active-filters" class="catalog-active-filters" aria-label="Filtros ativos">
                    <span class="catalog-active-filters__label">Filtros ativos:</span>
                    @foreach($activeLabels as $key => $label)<x-badge variant="info" class="catalog-chip" wire:key="active-{{ $key }}">{{ $label }}</x-badge>@endforeach
                    <div class="catalog-active-filters__actions">
                        @if(trim($search) !== '')<button type="button" wire:click="$set('search', '')" class="catalog-text-action">Limpar busca</button>@endif
                        <button type="button" wire:click="clearFilters" class="catalog-text-action">Limpar filtros</button>
                    </div>
                </div>
            @endif

            @if ($products->isEmpty())
                <div class="ds-card catalog-empty">
                    <x-icon name="magnifying-glass" />
                    <h3>Nenhum produto encontrado</h3>
                    <p>Ajuste a busca ou os filtros para encontrar outros produtos.</p>
                    <div>
                        <x-button type="button" variant="secondary" wire:click="clearFilters">Limpar filtros</x-button>
                        @if(trim($search) !== '')<x-button type="button" wire:click="$set('search', '')">Limpar busca</x-button>@endif
                    </div>
                </div>
            @else
                <div class="catalog-products">
                    @foreach ($products as $product)
                        <x-home-product-card :product="$product" :show-brand="true" wire:key="catalog-product-{{ $product->id }}" />
                    @endforeach
                </div>
                <div class="catalog-pagination-row">
                    <p>Mostrando {{ $products->firstItem() }} a {{ $products->lastItem() }} de {{ $products->total() }} resultados</p>
                    <div class="catalog-pagination-controls">
                        <div class="catalog-per-page">
                            <x-catalog-dropdown id="catalog-per-page" model="perPage" :options="[12 => '12 por página', 24 => '24 por página', 48 => '48 por página']" :value="$perPage" label="Produtos por página" />
                        </div>
                        {{ $products->links('components.catalog-pagination') }}
                    </div>
                </div>
            @endif
        </section>
    </div>
</div>
