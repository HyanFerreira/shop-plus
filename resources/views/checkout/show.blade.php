<x-app-layout>
    <div class="checkout-page"><div class="ds-storefront-container checkout-container">
        <nav class="checkout-breadcrumb" aria-label="Caminho de navegação"><a href="{{ route('cart.show') }}">Carrinho</a><span aria-hidden="true">›</span><span aria-current="page">Checkout</span></nav>
        <div class="checkout-heading"><p class="checkout-eyebrow">Etapa 1 de 2</p><h1>Finalizar pedido</h1><p>Revise seus produtos, endereço e entrega. O pagamento fictício vem depois.</p></div>
        <livewire:checkout-manager />
    </div></div>
</x-app-layout>
