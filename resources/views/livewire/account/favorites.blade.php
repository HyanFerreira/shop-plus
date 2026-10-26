<div class="account-favorites">
    @if($products->isEmpty())
        <section class="account-empty ds-panel">
            <x-icon name="heart" class="size-12" />
            <h2>Você ainda não tem favoritos disponíveis</h2>
            <p>Salve produtos pelo coração para encontrá-los aqui. Produtos retirados do catálogo deixam de aparecer nesta lista.</p>
            <a href="{{ route('catalog.index') }}" class="ds-button ds-button--primary ds-button--md">Explorar catálogo</a>
        </section>
    @else
        <div class="catalog-products">
            @foreach($products as $product)
                <x-home-product-card :product="$product" :show-availability="true" wire:key="favorite-product-{{ $product->id }}" />
            @endforeach
        </div>
        @if($products->hasPages())<div class="orders-pagination">{{ $products->links() }}</div>@endif
    @endif
</div>
